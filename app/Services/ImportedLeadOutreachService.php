<?php

namespace App\Services;

use App\Models\ConnectedMailbox;
use App\Models\ImportedLead;
use App\Models\ImportedOutreach;
use App\Models\ImportedOutreachRecipient;
use App\Models\SenderIdentity;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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

        $outreach = DB::transaction(function () use ($user, $leads, $payload, $deliveryMode, $attachmentMeta) {
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
                $toEmail = $lead->primaryEmail();
                if (! $toEmail) {
                    continue;
                }

                ImportedOutreachRecipient::create([
                    'imported_outreach_id' => $outreach->id,
                    'imported_lead_id' => $lead->id,
                    'tracking_id' => (string) Str::uuid(),
                    'to_email' => $toEmail,
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

                if ($deliveryMode === 'send') {
                    $body .= $this->trackingPixelHtml((string) $recipient->tracking_id);
                }

                $message = [
                    'subject' => $subject,
                    'html' => $body,
                    'to' => $recipient->to_email,
                    'attachments' => $graphAttachments,
                ];

                $graphResult = $deliveryMode === 'send'
                    ? $this->graphMail->send($accessToken, $message)
                    : $this->graphMail->createDraft($accessToken, $message);

                if ($graphResult['successful']) {
                    $recipient->update([
                        'subject' => $subject,
                        'final_body' => $body,
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
                    $recipient->update([
                        'subject' => $subject,
                        'final_body' => $body,
                        'status' => 'failed',
                        'failed_reason' => $graphResult['error'] ?? 'Graph API error.',
                    ]);
                    $failed++;
                    $results[] = [
                        'id' => $recipient->id,
                        'successful' => false,
                        'error' => $graphResult['error'] ?? 'Graph API error.',
                    ];
                }
            } catch (Throwable $e) {
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
        $src = rtrim((string) config('app.url'), '/').'/t/o/'.rawurlencode($trackingId).'.gif';

        return '<img src="'.e($src).'" width="1" height="1" style="display:none;" alt="" />';
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
