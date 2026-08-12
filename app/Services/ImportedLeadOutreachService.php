<?php

namespace App\Services;

use App\Models\ConnectedMailbox;
use App\Models\ImportedLead;
use App\Models\ImportedOutreach;
use App\Models\ImportedOutreachRecipient;
use App\Models\SenderIdentity;
use App\Models\User;
use App\Services\GraphSubscriptionService;
use App\Support\EmailTracking;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ImportedLeadOutreachService
{
    public function __construct(
        private readonly MicrosoftGraphMailService $graphMail = new MicrosoftGraphMailService,
    ) {}

    /**
     * @param  Collection<int, ImportedLead>|array<int, ImportedLead>  $leads
     * @param  array{
     *     delivery_mode: string,
     *     subject: string,
     *     body: string,
     *     email_signature?: ?string,
     *     sender_identity_id?: ?string,
     *     sender_address?: ?string,
     *     cc_emails?: list<string>,
     *     attachments?: list<array{path: string, name: string, contentType: string}>
     * }  $payload
     * @return array{outreach: ImportedOutreach, sent: int, drafted: int, failed: int, results: list<array<string, mixed>>}
     */
    public function dispatch(User $user, Collection|array $leads, array $payload): array
    {
        $leads = collect($leads)->values();
        if ($leads->isEmpty()) {
            throw new RuntimeException('No imported leads selected.');
        }

        $mailbox = ConnectedMailbox::query()
            ->where('user_id', $user->id)
            ->where('provider', 'microsoft')
            ->first();

        if (! $mailbox) {
            throw new RuntimeException('No connected Microsoft mailbox found. Connect Outlook first.');
        }

        $accessToken = ConnectedMailbox::getFreshAccessToken($mailbox);
        $deliveryMode = $payload['delivery_mode'] === 'send' ? 'send' : 'draft';
        $attachmentMeta = is_array($payload['attachments'] ?? null) ? $payload['attachments'] : [];
        $customCcEmails = is_array($payload['cc_emails'] ?? null) ? $payload['cc_emails'] : [];

        // Ensure inbox webhook subscription exists so replies can be captured.
        app(GraphSubscriptionService::class)->tryEnsureInboxSubscription($user);

        $outreach = DB::transaction(function () use ($user, $leads, $payload, $deliveryMode, $attachmentMeta, $customCcEmails) {
            $outreach = ImportedOutreach::create([
                'user_id' => $user->id,
                'sender_identity_id' => $payload['sender_identity_id'] ?? null,
                'name' => 'Import Outreach '.now()->format('M d, Y H:i'),
                'delivery_mode' => $deliveryMode,
                'subject_template' => $payload['subject'],
                'body_template' => $payload['body'],
                'email_signature' => $payload['email_signature'] ?? null,
                'attachments' => $attachmentMeta !== [] ? $attachmentMeta : null,
                'status' => 'processing',
            ]);

            foreach ($leads as $lead) {
                $addresses = $this->resolveUnifiedAddresses($lead, $customCcEmails);
                if ($addresses === null) {
                    continue;
                }

                ImportedOutreachRecipient::create([
                    'imported_outreach_id' => $outreach->id,
                    'imported_lead_id' => $lead->id,
                    'tracking_id' => (string) Str::uuid(),
                    'to_email' => $addresses['to'],
                    'cc_emails' => $addresses['cc'] !== [] ? $addresses['cc'] : null,
                    'subject' => null,
                    'status' => 'pending',
                ]);
            }

            return $outreach;
        });

        $outreach->load(['recipients.importedLead.emails']);

        if ($outreach->recipients->isEmpty()) {
            $outreach->update([
                'status' => 'failed',
                'error_message' => 'None of the selected leads have a valid email address.',
            ]);

            return [
                'outreach' => $outreach->fresh(),
                'sent' => 0,
                'drafted' => 0,
                'failed' => 0,
                'results' => [],
            ];
        }

        $sent = 0;
        $drafted = 0;
        $failed = 0;
        $results = [];
        $signature = (string) ($outreach->email_signature ?? '');
        $graphAttachments = $this->resolveAttachments($attachmentMeta);

        foreach ($outreach->recipients as $recipient) {
            $lead = $recipient->importedLead;
            if (! $lead) {
                $recipient->update([
                    'status' => 'failed',
                    'failed_reason' => 'Imported lead not found.',
                ]);
                $failed++;
                $results[] = ['id' => $recipient->id, 'successful' => false, 'error' => 'Imported lead not found.'];
                continue;
            }

            try {
                $subject = $this->substitute($outreach->subject_template, $lead, $recipient->to_email);
                $body = $this->substitute($outreach->body_template, $lead, $recipient->to_email);
                $body = $this->normalizeBodyHtml($body);

                if ($signature !== '') {
                    $body .= $signature;
                }

                $trackingId = (string) $recipient->tracking_id;
                $body = EmailTracking::appendToHtml($body, $trackingId);

                $message = [
                    'subject' => $subject,
                    'html' => $body,
                    'to' => $recipient->to_email,
                    'cc' => is_array($recipient->cc_emails) ? $recipient->cc_emails : [],
                    'attachments' => $graphAttachments,
                ];

                // Draft → send (not sendMail) so Graph returns a message id we can store.
                $graphResult = $deliveryMode === 'send'
                    ? $this->graphMail->send($accessToken, $message)
                    : $this->graphMail->createDraft($accessToken, $message);

                $graphMessageId = is_string($graphResult['graph_message_id'] ?? null)
                    ? $graphResult['graph_message_id']
                    : null;
                $internetMessageId = is_string($graphResult['internet_message_id'] ?? null)
                    ? $graphResult['internet_message_id']
                    : null;

                if ($graphResult['successful']) {
                    $recipient->update([
                        'subject' => $subject,
                        'final_body' => $body,
                        'graph_message_id' => $graphMessageId ?? $recipient->graph_message_id,
                        'message_id' => $internetMessageId ?? $recipient->message_id,
                        'status' => $deliveryMode === 'send' ? 'sent' : 'drafted',
                        'sent_at' => $deliveryMode === 'send' ? now() : null,
                        'drafted_at' => $deliveryMode === 'draft' ? now() : null,
                        'failed_reason' => null,
                    ]);

                    if ($deliveryMode === 'send') {
                        $sent++;
                    } else {
                        $drafted++;
                    }

                    $results[] = ['id' => $recipient->id, 'successful' => true, 'error' => null];
                } else {
                    $error = $graphResult['error'] ?? 'Graph API error.';

                    Log::error('Imported outreach Graph dispatch failed.', [
                        'recipient_id' => $recipient->id,
                        'imported_lead_id' => $recipient->imported_lead_id,
                        'delivery_mode' => $deliveryMode,
                        'graph_message_id' => $graphMessageId,
                        'status' => $graphResult['status'] ?? null,
                        'error' => $error,
                    ]);

                    $recipient->update([
                        'subject' => $subject,
                        'final_body' => $body,
                        // Keep draft id when create succeeded but /send failed.
                        'graph_message_id' => $graphMessageId ?? $recipient->graph_message_id,
                        'message_id' => $internetMessageId ?? $recipient->message_id,
                        'status' => 'failed',
                        'failed_reason' => $error,
                    ]);
                    $failed++;
                    $results[] = [
                        'id' => $recipient->id,
                        'successful' => false,
                        'error' => $error,
                    ];
                }
            } catch (Throwable $e) {
                Log::error('Imported outreach dispatch threw an exception.', [
                    'recipient_id' => $recipient->id,
                    'imported_lead_id' => $recipient->imported_lead_id,
                    'error' => $e->getMessage(),
                ]);

                $recipient->update([
                    'status' => 'failed',
                    'failed_reason' => $e->getMessage(),
                ]);
                $failed++;
                $results[] = ['id' => $recipient->id, 'successful' => false, 'error' => $e->getMessage()];
            }
        }

        $successCount = $sent + $drafted;
        $status = match (true) {
            $failed === 0 && $successCount > 0 => 'completed',
            $successCount > 0 => 'partial',
            default => 'failed',
        };

        $outreach->update([
            'status' => $status,
            'sent_at' => $sent > 0 ? now() : null,
            'error_message' => $failed > 0
                ? collect($results)->pluck('error')->filter()->first()
                : null,
        ]);

        return [
            'outreach' => $outreach->fresh(['recipients']),
            'sent' => $sent,
            'drafted' => $drafted,
            'failed' => $failed,
            'results' => $results,
        ];
    }

    /**
     * Send an in-app reply on an imported lead's outreach thread via Microsoft Graph.
     *
     * @return array{successful: bool, recipient: ?ImportedOutreachRecipient, error: ?string}
     */
    public function reply(User $user, ImportedLead $lead, string $body): array
    {
        $body = trim($body);
        if ($body === '') {
            throw new RuntimeException('Reply body is required.');
        }

        $mailbox = ConnectedMailbox::query()
            ->where('user_id', $user->id)
            ->where('provider', 'microsoft')
            ->first();

        if (! $mailbox) {
            throw new RuntimeException('No connected Microsoft mailbox found. Connect Outlook first.');
        }

        $accessToken = ConnectedMailbox::getFreshAccessToken($mailbox);
        app(GraphSubscriptionService::class)->tryEnsureInboxSubscription($user);

        $lead->load(['outreachRecipients.inboundMessages']);

        $sourceRecipient = $lead->outreachRecipients
            ->filter(fn (ImportedOutreachRecipient $r) => $r->status === 'sent')
            ->sortByDesc(fn (ImportedOutreachRecipient $r) => optional($r->sent_at ?? $r->created_at)?->getTimestamp() ?? 0)
            ->first();

        if (! $sourceRecipient) {
            throw new RuntimeException('Send an outreach email first before replying from the app.');
        }

        $latestInbound = $lead->outreachRecipients
            ->flatMap(fn (ImportedOutreachRecipient $r) => $r->inboundMessages)
            ->sortByDesc(fn ($inbound) => optional($inbound->received_at ?? $inbound->created_at)?->getTimestamp() ?? 0)
            ->first();

        $graphMessageId = null;
        if ($latestInbound && is_string($latestInbound->graph_message_id) && $latestInbound->graph_message_id !== '') {
            $graphMessageId = $latestInbound->graph_message_id;
        }

        if ($graphMessageId === null) {
            $internetMessageId = is_string($sourceRecipient->message_id) ? trim($sourceRecipient->message_id) : '';
            if ($internetMessageId === '') {
                throw new RuntimeException(
                    'Cannot thread this reply: the original outreach has no message id. Send a new outreach first.'
                );
            }

            $graphMessageId = $this->graphMail->findMessageIdByInternetMessageId($accessToken, $internetMessageId);
            if ($graphMessageId === null) {
                throw new RuntimeException(
                    'Could not find the original message in Outlook to reply to. Check the connected mailbox.'
                );
            }
        }

        $html = $this->normalizeBodyHtml($body);
        $trackingId = (string) Str::uuid();
        $html = EmailTracking::appendToHtml($html, $trackingId);

        $graphResult = $this->graphMail->replyToMessage($accessToken, $graphMessageId, $html);

        if (! $graphResult['successful']) {
            return [
                'successful' => false,
                'recipient' => null,
                'error' => $graphResult['error'] ?? 'Failed to send reply via Microsoft Graph.',
            ];
        }

        $subject = is_string($graphResult['subject'] ?? null) && $graphResult['subject'] !== ''
            ? $graphResult['subject']
            : $this->replySubject((string) ($sourceRecipient->subject ?? $latestInbound?->subject ?? 'Outreach'));

        $recipient = ImportedOutreachRecipient::create([
            'imported_outreach_id' => $sourceRecipient->imported_outreach_id,
            'imported_lead_id' => $lead->id,
            'tracking_id' => $trackingId,
            'graph_message_id' => $graphResult['graph_message_id'] ?? null,
            'message_id' => $graphResult['internet_message_id'] ?? null,
            'to_email' => $sourceRecipient->to_email,
            'cc_emails' => is_array($sourceRecipient->cc_emails) ? $sourceRecipient->cc_emails : null,
            'subject' => $subject,
            'final_body' => $html,
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        return [
            'successful' => true,
            'recipient' => $recipient,
            'error' => null,
        ];
    }

    private function replySubject(string $originalSubject): string
    {
        $subject = trim($originalSubject);
        if ($subject === '') {
            return 'Re:';
        }

        return preg_match('/^re:/i', $subject) === 1 ? $subject : 'Re: '.$subject;
    }

    public function buildSignatureHtml(
        array $validated,
        ?SenderIdentity $senderIdentity,
    ): ?string {
        $senderName = trim((string) ($validated['sender_name'] ?? '')) ?: trim((string) ($senderIdentity?->sender_name ?? ''));
        $senderRole = trim((string) ($validated['sender_role'] ?? '')) ?: trim((string) ($senderIdentity?->sender_role ?? ''));
        $senderCompany = trim((string) ($validated['sender_company'] ?? '')) ?: trim((string) ($senderIdentity?->sender_company ?? ''));
        $senderAddress = trim((string) ($validated['sender_address'] ?? ''));

        if ($senderName === '' && $senderRole === '' && $senderCompany === '' && $senderAddress === '') {
            $stored = trim((string) ($senderIdentity?->email_signature ?? ''));

            return $stored !== '' ? $stored : null;
        }

        $name = e($senderName);
        $role = e($senderRole);
        $company = e($senderCompany);
        $address = e($senderAddress);

        return "<br><br>--<br><strong>{$name}</strong><br>{$role}".($role !== '' && $company !== '' ? ' | ' : '')."{$company}<br>{$address}";
    }

    /**
     * Resolve To from the lead, and CC from the campaign form submission.
     * Submitted CCs are sanitized, deduped, and must not include the To address.
     *
     * @param  list<string>  $submittedCcEmails
     * @return array{to: string, cc: list<string>}|null
     */
    private function resolveUnifiedAddresses(ImportedLead $lead, array $submittedCcEmails = []): ?array
    {
        $seen = [];
        $ordered = [];

        foreach ($lead->emails as $emailRow) {
            $raw = trim((string) ($emailRow->email ?? ''));
            if ($raw === '' || ! filter_var($raw, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $key = strtolower($raw);
            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $ordered[] = [
                'email' => $raw,
                'is_primary' => (bool) $emailRow->is_primary,
            ];
        }

        if ($ordered === []) {
            return null;
        }

        $primaryIndex = null;
        foreach ($ordered as $index => $item) {
            if ($item['is_primary']) {
                $primaryIndex = $index;
                break;
            }
        }

        $primaryIndex ??= 0;
        $to = $ordered[$primaryIndex]['email'];
        $toKey = strtolower($to);

        $cc = [];
        $ccSeen = [$toKey => true];

        foreach ($submittedCcEmails as $extra) {
            $raw = trim((string) $extra);
            if ($raw === '' || ! filter_var($raw, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $key = strtolower($raw);
            if (isset($ccSeen[$key])) {
                continue;
            }

            $ccSeen[$key] = true;
            $cc[] = $raw;
        }

        return [
            'to' => $to,
            'cc' => $cc,
        ];
    }

    private function substitute(string $template, ImportedLead $lead, string $email): string
    {
        $map = [
            '{{fullName}}' => (string) ($lead->contact_name ?? ''),
            '{{contactName}}' => (string) ($lead->contact_name ?? ''),
            '{{companyName}}' => (string) ($lead->organization_name ?? ''),
            '{{organizationName}}' => (string) ($lead->organization_name ?? ''),
            '{{email}}' => $email,
            '{{address}}' => (string) ($lead->address ?? ''),
            '{{hyperline}}' => '',
        ];

        return str_replace(array_keys($map), array_values($map), $template);
    }

    private function normalizeBodyHtml(string $body): string
    {
        if (str_contains($body, '<') && str_contains($body, '>')) {
            return $body;
        }

        return nl2br(e($body), false);
    }

    private function trackingPixelHtml(string $trackingId): string
    {
        return EmailTracking::pixelHtml($trackingId);
    }

    private function rewriteLinksForTracking(string $html, string $trackingId): string
    {
        return EmailTracking::rewriteLinks($html, $trackingId);
    }

    /**
     * @param  list<array{path?: string, name?: string, contentType?: string}|string>  $stored
     * @return list<array{name: string, contentType: string, contentBytes: string}>
     */
    private function resolveAttachments(array $stored): array
    {
        $attachments = [];

        foreach ($stored as $item) {
            $attachment = $this->buildAttachmentPayload($item);
            if ($attachment !== null) {
                $attachments[] = $attachment;
            }
        }

        return $attachments;
    }

    /**
     * @param  array{path?: string, name?: string, contentType?: string}|string  $item
     * @return array{name: string, contentType: string, contentBytes: string}|null
     */
    private function buildAttachmentPayload(array|string $item): ?array
    {
        if (is_string($item)) {
            $path = ltrim($item, '/');
            $name = basename($path);
            $contentType = $this->guessContentType($path);
        } else {
            $path = ltrim((string) ($item['path'] ?? ''), '/');
            $name = (string) ($item['name'] ?? basename($path));
            $contentType = (string) ($item['contentType'] ?? $this->guessContentType($path));
        }

        if ($path === '' || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        return [
            'name' => $name,
            'contentType' => $contentType !== '' ? $contentType : 'application/octet-stream',
            'contentBytes' => base64_encode(Storage::disk('public')->get($path)),
        ];
    }

    private function guessContentType(string $path): string
    {
        $fullPath = Storage::disk('public')->path($path);

        if (! is_file($fullPath)) {
            return 'application/octet-stream';
        }

        $detected = mime_content_type($fullPath);

        return is_string($detected) && $detected !== ''
            ? $detected
            : 'application/octet-stream';
    }
}
