<?php

namespace App\Jobs;

use App\Exceptions\GraphNotFoundException;
use App\Exceptions\GraphRateLimitedException;
use App\Models\ConnectedMailbox;
use App\Models\ImportedOutreach;
use App\Models\ImportedOutreachRecipient;
use App\Services\MicrosoftGraphMailService;
use App\Support\EmailTracking;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ProcessBulkReplyJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 10;

    public int $timeout = 300;

    /** Direct attach under this size; larger files use an upload session. */
    private const SMALL_ATTACHMENT_MAX_BYTES = 3 * 1024 * 1024;

    /** Graph recommends upload fragments that are multiples of 320 KiB. */
    private const UPLOAD_CHUNK_BYTES = 320 * 1024 * 4; // 1,280 KiB

    public function __construct(
        public ImportedOutreachRecipient $recipient,
    ) {}

    public function handle(MicrosoftGraphMailService $graphMail): void
    {
        $this->recipient->refresh();

        if ($this->recipient->status === 'sent') {
            return;
        }

        $childOutreach = $this->recipient->outreach()->with('parent')->first();
        if (! $childOutreach instanceof ImportedOutreach) {
            $this->markFailed('Child outreach campaign was not found.');

            return;
        }

        $parentRecipient = $this->parentRecipient($childOutreach);
        if ($parentRecipient === null) {
            $this->markFailed('Original outreach recipient was not found for threaded reply.');

            return;
        }

        $toEmail = trim((string) $this->recipient->to_email);
        if ($toEmail === '') {
            $this->markFailed('Recipient email address is missing for bulk reply.');

            return;
        }

        $ccEmails = is_array($this->recipient->cc_emails) ? $this->recipient->cc_emails : [];

        $mailbox = ConnectedMailbox::query()
            ->where('user_id', $childOutreach->user_id)
            ->where('provider', 'microsoft')
            ->first();

        if ($mailbox === null) {
            $this->markFailed('No connected Microsoft mailbox found.');

            return;
        }

        try {
            $accessToken = ConnectedMailbox::getFreshAccessToken($mailbox);
        } catch (Throwable $e) {
            $this->markFailed('Failed to refresh Microsoft access token: '.$e->getMessage());

            return;
        }

        $replyTarget = $this->resolveReplyTarget($graphMail, $accessToken, $parentRecipient);
        if ($replyTarget === null) {
            $this->markFailed('Original Graph message id was not found for threaded reply.');

            return;
        }

        $originalGraphMessageId = $replyTarget['graph_message_id'];
        $forceRecipients = $replyTarget['force_recipients'];
        $deliveredToEmail = $replyTarget['reply_to_email'] ?? $toEmail;

        if (! filled($this->recipient->tracking_id)) {
            $this->recipient->forceFill([
                'tracking_id' => (string) Str::uuid(),
            ])->save();
        }

        $htmlBody = EmailTracking::appendToHtml(
            $this->buildReplyHtml($childOutreach),
            (string) $this->recipient->tracking_id
        );

        // Step 1: createReply (prefer inbound lead message; force To when replying to Sent Items)
        try {
            $draftId = $this->createReplyDraft(
                $accessToken,
                $originalGraphMessageId,
                $htmlBody,
                $toEmail,
                $ccEmails,
                $forceRecipients
            );
        } catch (GraphRateLimitedException $e) {
            $this->releaseAfterThrottle($e->retryAfterSeconds);

            return;
        } catch (GraphNotFoundException $e) {
            $resolvedTarget = $this->resolveReplyTarget(
                $graphMail,
                $accessToken,
                $parentRecipient,
                refreshSentItemsId: true
            );

            if ($resolvedTarget !== null && $resolvedTarget['graph_message_id'] !== $originalGraphMessageId) {
                try {
                    $draftId = $this->createReplyDraft(
                        $accessToken,
                        $resolvedTarget['graph_message_id'],
                        $htmlBody,
                        $toEmail,
                        $ccEmails,
                        $resolvedTarget['force_recipients']
                    );
                    $originalGraphMessageId = $resolvedTarget['graph_message_id'];
                    $forceRecipients = $resolvedTarget['force_recipients'];
                    $deliveredToEmail = $resolvedTarget['reply_to_email'] ?? $toEmail;
                } catch (GraphRateLimitedException $throttled) {
                    $this->releaseAfterThrottle($throttled->retryAfterSeconds);

                    return;
                } catch (Throwable $retryError) {
                    Log::warning('Bulk reply aborted after reply target retry.', [
                        'recipient_id' => $this->recipient->id,
                        'original_graph_message_id' => $originalGraphMessageId,
                        'resolved_graph_message_id' => $resolvedTarget['graph_message_id'],
                        'error' => $retryError->getMessage(),
                    ]);

                    $this->markFailed('Could not find the original outreach in Outlook to reply to.');

                    return;
                }
            } else {
                Log::warning('Bulk reply aborted: reply target Graph message missing (404).', [
                    'recipient_id' => $this->recipient->id,
                    'original_graph_message_id' => $originalGraphMessageId,
                    'error' => $e->getMessage(),
                ]);

                $this->markFailed('Could not find the original outreach in Outlook to reply to.');

                return;
            }
        } catch (Throwable $e) {
            $this->markFailed($e->getMessage());

            return;
        }

        // Step 2: attachments (Safeguard A — size split)
        try {
            $this->attachFiles($accessToken, $draftId, $childOutreach);
        } catch (GraphRateLimitedException $e) {
            $this->releaseAfterThrottle($e->retryAfterSeconds);

            return;
        } catch (Throwable $e) {
            $this->markFailed('Attachment upload failed: '.$e->getMessage());

            return;
        }

        // Step 3: send
        try {
            $this->sendDraft($accessToken, $draftId);
        } catch (GraphRateLimitedException $e) {
            $this->releaseAfterThrottle($e->retryAfterSeconds);

            return;
        } catch (Throwable $e) {
            $this->markFailed('Failed to send reply: '.$e->getMessage());

            return;
        }

        $this->recipient->update([
            'status' => 'sent',
            'graph_message_id' => $draftId,
            'to_email' => $deliveredToEmail !== '' ? $deliveredToEmail : $this->recipient->to_email,
            'subject' => $childOutreach->subject_template,
            'final_body' => $htmlBody,
            'sent_at' => now(),
            'failed_reason' => null,
        ]);

        $this->refreshChildOutreachStatus($childOutreach);

        Log::info('Bulk reply sent via Microsoft Graph.', [
            'recipient_id' => $this->recipient->id,
            'graph_message_id' => $draftId,
            'original_graph_message_id' => $originalGraphMessageId,
            'to_email' => $toEmail,
            'forced_recipients' => $forceRecipients,
        ]);
    }

    /**
     * @return array{graph_message_id: string, force_recipients: bool, reply_to_email: ?string}|null
     */
    private function resolveReplyTarget(
        MicrosoftGraphMailService $graphMail,
        string $accessToken,
        ImportedOutreachRecipient $parentRecipient,
        bool $refreshSentItemsId = false,
    ): ?array {
        $parentRecipient->loadMissing('inboundMessages');

        $latestInbound = $parentRecipient->inboundMessages
            ->sortByDesc(fn ($inbound) => optional($inbound->received_at ?? $inbound->created_at)?->getTimestamp() ?? 0)
            ->first();

        $inboundGraphId = is_string($latestInbound?->graph_message_id)
            ? trim($latestInbound->graph_message_id)
            : '';

        if ($inboundGraphId !== '') {
            $inboundFrom = is_string($latestInbound?->from_email)
                ? trim($latestInbound->from_email)
                : '';

            return [
                'graph_message_id' => $inboundGraphId,
                'force_recipients' => false,
                'reply_to_email' => $inboundFrom !== '' ? $inboundFrom : null,
            ];
        }

        if ($refreshSentItemsId) {
            $this->refreshParentGraphMessageId($graphMail, $accessToken, $parentRecipient);
            $parentRecipient->refresh();
        }

        $sentGraphId = $this->graphIdFromRecipient($parentRecipient);
        if ($sentGraphId === null) {
            $sentGraphId = $this->refreshParentGraphMessageId($graphMail, $accessToken, $parentRecipient);
        }

        if ($sentGraphId === null) {
            return null;
        }

        // createReply on a Sent Items message replies to its sender (you). Override To to the lead.
        $parentTo = is_string($parentRecipient->to_email)
            ? trim($parentRecipient->to_email)
            : '';

        return [
            'graph_message_id' => $sentGraphId,
            'force_recipients' => true,
            'reply_to_email' => $parentTo !== '' ? $parentTo : null,
        ];
    }

    private function parentRecipient(ImportedOutreach $childOutreach): ?ImportedOutreachRecipient
    {
        $parent = $childOutreach->parent;
        if ($parent === null) {
            return null;
        }

        return ImportedOutreachRecipient::query()
            ->where('imported_outreach_id', $parent->id)
            ->where('imported_lead_id', $this->recipient->imported_lead_id)
            ->where(function ($query) {
                $query->whereNotNull('graph_message_id')
                    ->orWhereNotNull('message_id')
                    ->orWhereHas('inboundMessages', fn ($q) => $q->whereNotNull('graph_message_id'));
            })
            ->orderByDesc('sent_at')
            ->orderByDesc('created_at')
            ->first();
    }

    private function graphIdFromRecipient(?ImportedOutreachRecipient $parentRecipient): ?string
    {
        $graphMessageId = is_string($parentRecipient?->graph_message_id)
            ? trim($parentRecipient->graph_message_id)
            : '';

        return $graphMessageId !== '' ? $graphMessageId : null;
    }

    private function refreshParentGraphMessageId(
        MicrosoftGraphMailService $graphMail,
        string $accessToken,
        ?ImportedOutreachRecipient $parentRecipient,
    ): ?string {
        $internetMessageId = is_string($parentRecipient?->message_id)
            ? trim($parentRecipient->message_id)
            : '';

        if ($internetMessageId === '') {
            return null;
        }

        $resolvedId = $graphMail->findMessageIdByInternetMessageId($accessToken, $internetMessageId);
        if ($resolvedId === null) {
            return null;
        }

        if ($parentRecipient->graph_message_id !== $resolvedId) {
            $parentRecipient->update(['graph_message_id' => $resolvedId]);
        }

        return $resolvedId;
    }

    private function buildReplyHtml(ImportedOutreach $childOutreach): string
    {
        $body = trim((string) $childOutreach->body_template);
        if ($body === '') {
            return '<p></p>';
        }

        if (str_contains($body, '<') && str_contains($body, '>')) {
            $html = $body;
        } else {
            $html = nl2br(e($body), false);
        }

        $signature = trim((string) ($childOutreach->email_signature ?? ''));
        if ($signature !== '') {
            $html .= $signature;
        }

        return $html;
    }

    /**
     * POST /me/messages/{id}/createReply then ensure HTML body via PATCH.
     *
     * @param  list<string>  $ccEmails
     *
     * @throws GraphNotFoundException
     * @throws GraphRateLimitedException
     * @throws RuntimeException
     */
    private function createReplyDraft(
        string $accessToken,
        string $originalGraphMessageId,
        string $htmlBody,
        string $toEmail,
        array $ccEmails = [],
        bool $forceRecipients = false,
    ): string {
        $url = 'https://graph.microsoft.com/v1.0/me/messages/'
            .rawurlencode($originalGraphMessageId)
            .'/createReply';

        $response = $this->graphPost($accessToken, $url, [
            'message' => [
                'body' => [
                    'contentType' => 'HTML',
                    'content' => $htmlBody,
                ],
            ],
        ]);

        $this->throwIfGraphError($response, 'createReply');

        $draftId = $response->json('id');
        if (! is_string($draftId) || $draftId === '') {
            throw new RuntimeException('Graph createReply succeeded but returned no draft id.');
        }

        $patchPayload = [
            'body' => [
                'contentType' => 'HTML',
                'content' => $htmlBody,
            ],
        ];

        if ($forceRecipients) {
            $patchPayload['toRecipients'] = [[
                'emailAddress' => [
                    'address' => $toEmail,
                ],
            ]];
        }

        // Always re-apply stored CC so imported / form CC survives both inbound and Sent Items replies.
        $cc = array_values(array_filter(
            $ccEmails,
            fn ($email) => is_string($email)
                && trim($email) !== ''
                && filter_var(trim($email), FILTER_VALIDATE_EMAIL)
                && strtolower(trim($email)) !== strtolower($toEmail)
        ));

        if ($cc !== []) {
            $seen = [];
            $patchPayload['ccRecipients'] = [];
            foreach ($cc as $email) {
                $address = trim($email);
                $key = strtolower($address);
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $patchPayload['ccRecipients'][] = [
                    'emailAddress' => [
                        'address' => $address,
                    ],
                ];
            }
        }

        $patch = $this->graphPatch(
            $accessToken,
            'https://graph.microsoft.com/v1.0/me/messages/'.rawurlencode($draftId),
            $patchPayload
        );

        $this->throwIfGraphError($patch, 'patch reply body');

        return $draftId;
    }

    /**
     * @throws GraphRateLimitedException
     * @throws RuntimeException
     */
    private function attachFiles(string $accessToken, string $draftId, ImportedOutreach $childOutreach): void
    {
        $attachments = is_array($childOutreach->attachments) ? $childOutreach->attachments : [];
        if ($attachments === []) {
            return;
        }

        foreach ($attachments as $attachment) {
            if (! is_array($attachment)) {
                continue;
            }

            $path = ltrim((string) ($attachment['path'] ?? ''), '/');
            $disk = (string) ($attachment['disk'] ?? 'local');
            $name = (string) ($attachment['name'] ?? basename($path));
            $contentType = (string) ($attachment['contentType'] ?? 'application/octet-stream');

            if ($path === '' || ! Storage::disk($disk)->exists($path)) {
                Log::warning('Bulk reply attachment missing on disk; skipping.', [
                    'recipient_id' => $this->recipient->id,
                    'path' => $path,
                    'disk' => $disk,
                ]);

                continue;
            }

            $size = (int) Storage::disk($disk)->size($path);
            $binary = Storage::disk($disk)->get($path);

            if ($size < self::SMALL_ATTACHMENT_MAX_BYTES) {
                $this->attachSmallFile($accessToken, $draftId, $name, $contentType, $binary);
            } else {
                $this->attachLargeFileViaUploadSession(
                    $accessToken,
                    $draftId,
                    $name,
                    $contentType,
                    $size,
                    $binary
                );
            }
        }
    }

    /**
     * @throws GraphRateLimitedException
     * @throws RuntimeException
     */
    private function attachSmallFile(
        string $accessToken,
        string $draftId,
        string $name,
        string $contentType,
        string $binary,
    ): void {
        $response = $this->graphPost(
            $accessToken,
            'https://graph.microsoft.com/v1.0/me/messages/'.rawurlencode($draftId).'/attachments',
            [
                '@odata.type' => '#microsoft.graph.fileAttachment',
                'name' => $name,
                'contentType' => $contentType !== '' ? $contentType : 'application/octet-stream',
                'contentBytes' => base64_encode($binary),
            ]
        );

        $this->throwIfGraphError($response, 'attach small file');
    }

    /**
     * Large attachment upload session with Content-Range chunking.
     *
     * @throws GraphRateLimitedException
     * @throws RuntimeException
     */
    private function attachLargeFileViaUploadSession(
        string $accessToken,
        string $draftId,
        string $name,
        string $contentType,
        int $size,
        string $binary,
    ): void {
        $sessionResponse = $this->graphPost(
            $accessToken,
            'https://graph.microsoft.com/v1.0/me/messages/'.rawurlencode($draftId).'/attachments/createUploadSession',
            [
                'AttachmentItem' => [
                    'attachmentType' => 'file',
                    'name' => $name,
                    'size' => $size,
                    'contentType' => $contentType !== '' ? $contentType : 'application/octet-stream',
                ],
            ]
        );

        $this->throwIfGraphError($sessionResponse, 'createUploadSession');

        $uploadUrl = $sessionResponse->json('uploadUrl');
        if (! is_string($uploadUrl) || $uploadUrl === '') {
            throw new RuntimeException('Graph createUploadSession returned no uploadUrl.');
        }

        $offset = 0;
        $length = strlen($binary);

        while ($offset < $length) {
            $chunk = substr($binary, $offset, self::UPLOAD_CHUNK_BYTES);
            $chunkSize = strlen($chunk);
            $start = $offset;
            $end = $offset + $chunkSize - 1;

            $chunkResponse = Http::withoutVerifying()
                ->timeout(120)
                ->withHeaders([
                    'Content-Length' => (string) $chunkSize,
                    'Content-Range' => "bytes {$start}-{$end}/{$length}",
                    'Content-Type' => 'application/octet-stream',
                ])
                ->withBody($chunk, 'application/octet-stream')
                ->put($uploadUrl);

            if ($chunkResponse->status() === 429) {
                throw new GraphRateLimitedException($this->retryAfterSeconds($chunkResponse));
            }

            // 200/201 = completed attachment; 202 = accepted fragment.
            if (! in_array($chunkResponse->status(), [200, 201, 202], true)) {
                $message = $chunkResponse->json('error.message')
                    ?? $chunkResponse->body()
                    ?: 'Chunk upload failed.';

                throw new RuntimeException(
                    is_string($message) ? $message : 'Chunk upload failed.'
                );
            }

            $offset += $chunkSize;
        }
    }

    /**
     * @throws GraphRateLimitedException
     * @throws RuntimeException
     */
    private function sendDraft(string $accessToken, string $draftId): void
    {
        $response = $this->graphPost(
            $accessToken,
            'https://graph.microsoft.com/v1.0/me/messages/'.rawurlencode($draftId).'/send',
            []
        );

        $this->throwIfGraphError($response, 'send reply');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function graphPost(string $accessToken, string $url, array $payload): Response
    {
        $pending = Http::withoutVerifying()
            ->withToken($accessToken)
            ->acceptJson()
            ->timeout(120);

        return $payload === []
            ? $pending->post($url)
            : $pending->asJson()->post($url, $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function graphPatch(string $accessToken, string $url, array $payload): Response
    {
        return Http::withoutVerifying()
            ->withToken($accessToken)
            ->acceptJson()
            ->timeout(120)
            ->asJson()
            ->patch($url, $payload);
    }

    /**
     * @throws GraphNotFoundException
     * @throws GraphRateLimitedException
     * @throws RuntimeException
     */
    private function throwIfGraphError(Response $response, string $action): void
    {
        if ($response->successful()) {
            return;
        }

        if ($response->status() === 404) {
            throw new GraphNotFoundException("Graph {$action} returned 404.");
        }

        if ($response->status() === 429) {
            throw new GraphRateLimitedException($this->retryAfterSeconds($response));
        }

        $message = $response->json('error.message')
            ?? $response->body()
            ?: "Graph {$action} failed.";

        throw new RuntimeException(
            is_string($message) ? $message : "Graph {$action} failed."
        );
    }

    private function retryAfterSeconds(Response $response): int
    {
        $header = $response->header('Retry-After');
        if (is_numeric($header)) {
            return max(1, (int) $header);
        }

        return 60;
    }

    private function releaseAfterThrottle(int $seconds): void
    {
        $seconds = max(1, $seconds);

        Log::warning('Bulk reply throttled by Microsoft Graph (429); releasing job.', [
            'recipient_id' => $this->recipient->id,
            'retry_after' => $seconds,
            'attempt' => $this->attempts(),
        ]);

        $this->release($seconds);
    }

    private function markFailed(string $reason): void
    {
        $this->recipient->update([
            'status' => 'failed',
            'failed_reason' => $reason,
        ]);

        $outreach = $this->recipient->outreach;
        if ($outreach instanceof ImportedOutreach) {
            $this->refreshChildOutreachStatus($outreach);
        }

        Log::error('Bulk reply failed.', [
            'recipient_id' => $this->recipient->id,
            'reason' => $reason,
        ]);
    }

    private function refreshChildOutreachStatus(ImportedOutreach $outreach): void
    {
        $outreach->load('recipients');

        $total = $outreach->recipients->count();
        if ($total === 0) {
            return;
        }

        $sent = $outreach->recipients->where('status', 'sent')->count();
        $failed = $outreach->recipients->where('status', 'failed')->count();
        $pending = $total - $sent - $failed;

        $status = match (true) {
            $pending > 0 => 'processing',
            $failed === 0 && $sent > 0 => 'completed',
            $sent > 0 && $failed > 0 => 'partial',
            $failed === $total => 'failed',
            default => 'processing',
        };

        $outreach->update([
            'status' => $status,
            'sent_at' => $sent > 0 ? ($outreach->sent_at ?? now()) : $outreach->sent_at,
            'error_message' => $failed > 0
                ? $outreach->recipients->firstWhere('status', 'failed')?->failed_reason
                : null,
        ]);
    }
}
