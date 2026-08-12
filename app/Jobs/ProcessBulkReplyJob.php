<?php

namespace App\Jobs;

use App\Exceptions\GraphNotFoundException;
use App\Exceptions\GraphRateLimitedException;
use App\Models\ConnectedMailbox;
use App\Models\ImportedOutreach;
use App\Models\ImportedOutreachRecipient;
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

    public function handle(): void
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

        $originalGraphMessageId = $this->resolveOriginalGraphMessageId($childOutreach);
        if ($originalGraphMessageId === null) {
            $this->markFailed('Original Graph message id was not found for threaded reply.');

            return;
        }

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

        if (! filled($this->recipient->tracking_id)) {
            $this->recipient->forceFill([
                'tracking_id' => (string) Str::uuid(),
            ])->save();
        }

        $htmlBody = EmailTracking::appendToHtml(
            $this->buildReplyHtml($childOutreach),
            (string) $this->recipient->tracking_id
        );

        // Step 1: createReply (Safeguard B — 404 ghost emails)
        try {
            $draftId = $this->createReplyDraft($accessToken, $originalGraphMessageId, $htmlBody);
        } catch (GraphRateLimitedException $e) {
            $this->releaseAfterThrottle($e->retryAfterSeconds);

            return;
        } catch (GraphNotFoundException $e) {
            Log::warning('Bulk reply aborted: original Graph message missing (404).', [
                'recipient_id' => $this->recipient->id,
                'original_graph_message_id' => $originalGraphMessageId,
                'error' => $e->getMessage(),
            ]);

            $this->markFailed('Original email deleted from Sent Items');

            return;
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
        ]);
    }

    private function resolveOriginalGraphMessageId(ImportedOutreach $childOutreach): ?string
    {
        $parent = $childOutreach->parent;
        if ($parent === null) {
            return null;
        }

        $parentRecipient = ImportedOutreachRecipient::query()
            ->where('imported_outreach_id', $parent->id)
            ->where('imported_lead_id', $this->recipient->imported_lead_id)
            ->whereNotNull('graph_message_id')
            ->orderByDesc('sent_at')
            ->orderByDesc('created_at')
            ->first();

        $graphMessageId = is_string($parentRecipient?->graph_message_id)
            ? trim($parentRecipient->graph_message_id)
            : '';

        return $graphMessageId !== '' ? $graphMessageId : null;
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
     * @throws GraphNotFoundException
     * @throws GraphRateLimitedException
     * @throws RuntimeException
     */
    private function createReplyDraft(string $accessToken, string $originalGraphMessageId, string $htmlBody): string
    {
        $url = 'https://graph.microsoft.com/v1.0/me/messages/'
            .rawurlencode($originalGraphMessageId)
            .'/createReply';

        // Prefer embedding body on createReply; PATCH below is the reliable fallback.
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

        $patch = $this->graphPatch(
            $accessToken,
            'https://graph.microsoft.com/v1.0/me/messages/'.rawurlencode($draftId),
            [
                'body' => [
                    'contentType' => 'HTML',
                    'content' => $htmlBody,
                ],
            ]
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
