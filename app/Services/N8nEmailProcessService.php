<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\ConnectedMailbox;
use App\Models\Lead;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Dispatches campaign recipients to the n8n email-automation webhook one at a time.
 * Each POST uses the async per-lead payload schema expected by the n8n workflow.
 */
class N8nEmailProcessService
{
    /**
     * @return array{successful: bool, dispatched: int, failed: int, results: array<int, array<string, mixed>>}
     */
    public function send(Campaign $campaign): array
    {
        $webhookUrl = config('services.n8n.email_process_webhook_url');
        $timeout = (int) config('services.n8n.timeout', 300);

        if (! $webhookUrl) {
            return [
                'successful' => false,
                'dispatched' => 0,
                'failed' => 0,
                'results' => [[
                    'successful' => false,
                    'error' => 'n8n email process webhook URL is not configured (N8N_EMAIL_PROCESS_WEBHOOK_URL).',
                ]],
            ];
        }

        try {
            $accessToken = $this->resolveMicrosoftAccessToken($campaign);
        } catch (Throwable $e) {
            return [
                'successful' => false,
                'dispatched' => 0,
                'failed' => 0,
                'results' => [[
                    'successful' => false,
                    'error' => $e->getMessage(),
                ]],
            ];
        }

        $campaign->loadMissing(['campaignRecipients.lead', 'senderIdentity']);

        $results = [];
        $dispatched = 0;
        $failed = 0;

        foreach ($campaign->campaignRecipients as $recipient) {
            $result = $this->dispatchRecipient($campaign, $recipient, $accessToken, $webhookUrl, $timeout);
            $results[] = $result;

            if ($result['successful']) {
                $dispatched++;
            } else {
                $failed++;
            }
        }

        if ($dispatched > 0) {
            $campaign->update(['sent_to_n8n_at' => now()]);
        }

        return [
            'successful' => $failed === 0 && $dispatched > 0,
            'dispatched' => $dispatched,
            'failed' => $failed,
            'results' => $results,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildRecipientPayload(
        Campaign $campaign,
        CampaignRecipient $recipient,
        string $microsoftAccessToken,
    ): array {
        if (! $recipient->relationLoaded('lead')) {
            $recipient->load('lead');
        }

        if (! $recipient->lead instanceof Lead) {
            throw new \RuntimeException("Campaign recipient {$recipient->id} has no associated lead.");
        }

        $email = $this->resolveLeadEmail($recipient->lead);
        if ($email === null) {
            throw new \RuntimeException("Campaign recipient {$recipient->id} has no deliverable email address.");
        }

        if ($recipient->tracking_id === null) {
            $recipient->tracking_id = (string) Str::uuid();
            $recipient->save();
        }

        $sender = $campaign->senderIdentity;
        $deliveryMode = $campaign->delivery_mode === 'send' ? 'send' : 'draft';
        $subject = $this->substituteTemplateVariables(
            (string) ($recipient->subject ?? ''),
            $recipient->lead,
            $email,
        );

        $mainBody = $this->prepareMainBody(
            (string) $campaign->email_main_body,
            $recipient->lead,
            $email,
            (string) $recipient->tracking_id,
        );

        return [
            'system_context' => [
                'campaign_recipient_id' => $recipient->id,
                'tracking_id' => $recipient->tracking_id,
                'delivery_mode' => $deliveryMode,
                'microsoft_access_token' => $microsoftAccessToken,
                'app_url' => rtrim((string) config('app.url'), '/'),
            ],
            'sender_context' => [
                'senderName' => (string) ($sender?->sender_name ?? ''),
                'senderRole' => (string) ($sender?->sender_role ?? ''),
                'senderCompany' => (string) ($sender?->sender_company ?? ''),
                'senderAddress' => $this->resolveSenderAddress($campaign),
            ],
            'campaign_context' => $this->buildCampaignContext($campaign, $subject, $mainBody),
            'lead_context' => [
                'fullName' => (string) ($recipient->lead->full_name ?? ''),
                'email' => $email,
                'position' => (string) ($recipient->lead->position ?? $recipient->lead->job_title ?? ''),
                'companyName' => (string) ($recipient->lead->company_name ?? ''),
                'companyWebsite' => (string) ($recipient->lead->company_website ?? ''),
                'companyLinkedin' => (string) ($recipient->lead->company_linkedin ?? ''),
                'companyDescription' => (string) ($recipient->lead->company_description ?? ''),
                'companyTechnology' => (string) ($recipient->lead->company_technology ?? ''),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function dispatchRecipient(
        Campaign $campaign,
        CampaignRecipient $recipient,
        string $accessToken,
        string $webhookUrl,
        int $timeout,
    ): array {
        try {
            $payload = $this->buildRecipientPayload($campaign, $recipient, $accessToken);
        } catch (Throwable $e) {
            return [
                'campaign_recipient_id' => $recipient->id,
                'successful' => false,
                'status' => null,
                'body' => null,
                'error' => $e->getMessage(),
            ];
        }

        try {
            $response = Http::withoutVerifying()
                ->timeout($timeout)
                ->asJson()
                ->post($webhookUrl, $payload);

            if ($response->successful()) {
                $recipient->update(['status' => 'queued']);
            } else {
                $recipient->update([
                    'status' => 'failed',
                    'failed_at' => now(),
                    'failed_reason' => 'Received non-success HTTP status from n8n email webhook.',
                ]);
            }

            return [
                'campaign_recipient_id' => $recipient->id,
                'successful' => $response->successful(),
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
                'error' => $response->successful() ? null : 'Received non-success HTTP status from n8n email webhook.',
            ];
        } catch (Throwable $e) {
            $recipient->update([
                'status' => 'failed',
                'failed_at' => now(),
                'failed_reason' => $e->getMessage(),
            ]);

            return [
                'campaign_recipient_id' => $recipient->id,
                'successful' => false,
                'status' => null,
                'body' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function resolveMicrosoftAccessToken(Campaign $campaign): string
    {
        $mailbox = ConnectedMailbox::where('user_id', $campaign->user_id)
            ->where('provider', 'microsoft')
            ->first();

        if (! $mailbox) {
            throw new \RuntimeException('No connected Microsoft mailbox found for this user. Connect Outlook first.');
        }

        return ConnectedMailbox::getFreshAccessToken($mailbox);
    }

    private function resolveSenderAddress(Campaign $campaign): string
    {
        return (string) ($campaign->n8n_response['sender_address'] ?? '');
    }

    /**
     * @return array<string, mixed>
     */
    private function buildCampaignContext(Campaign $campaign, string $subject, string $mainBody): array
    {
        $context = [
            'subject' => $subject,
            'mainBody' => $mainBody,
        ];

        $attachments = $this->resolveCampaignAttachments($campaign);

        if (count($attachments) === 1) {
            $context['attachment'] = $attachments[0];
        } elseif (count($attachments) > 1) {
            $context['attachments'] = $attachments;
        }

        return $context;
    }

    /**
     * @return array<int, array{name: string, contentType: string, contentBytes: string}>
     */
    private function resolveCampaignAttachments(Campaign $campaign): array
    {
        $stored = $campaign->n8n_response['attachments'] ?? [];

        if (! is_array($stored)) {
            return [];
        }

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
     * @param  array<string, mixed>|string  $item
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
            'contentType' => $contentType,
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

    public function prepareMainBody(string $html, Lead $lead, string $email, string $trackingId): string
    {
        $withTrackingLinks = $this->rewriteLinksForTracking($html);
        $withTrackingLinks = str_replace('__TRACKING_ID__', $trackingId, $withTrackingLinks);

        return $this->substituteTemplateVariables($withTrackingLinks, $lead, $email);
    }

    public function rewriteLinksForTracking(string $html): string
    {
        return (string) preg_replace_callback(
            '/<a\s+([^>]*\s)?href=(["\'])([^"\']+)\2/i',
            function (array $matches): string {
                $prefix = $matches[1] ?? '';
                $targetUrl = $matches[3];
                $trackingUrl = url('/t/c/__TRACKING_ID__').'?url='.urlencode($targetUrl);

                return '<a '.$prefix.'href="'.$trackingUrl.'"';
            },
            $html
        );
    }

    public function substituteTemplateVariables(string $template, Lead $lead, string $email): string
    {
        $hyperlineToken = '__HYPERLINE_TOKEN__'.Str::uuid()->toString();
        $protected = str_replace('{{hyperline}}', $hyperlineToken, $template);

        $replacements = [
            '{{fullName}}' => (string) ($lead->full_name ?? ''),
            '{{companyName}}' => (string) ($lead->company_name ?? ''),
            '{{position}}' => (string) ($lead->position ?? $lead->job_title ?? ''),
            '{{email}}' => $email,
            '{{companyWebsite}}' => (string) ($lead->company_website ?? ''),
            '{{companyLinkedin}}' => (string) ($lead->company_linkedin ?? ''),
            '{{companyDescription}}' => (string) ($lead->company_description ?? ''),
            '{{companyTechnology}}' => (string) ($lead->company_technology ?? ''),
        ];

        foreach ($replacements as $placeholder => $value) {
            $protected = str_replace($placeholder, $value, $protected);
        }

        return str_replace($hyperlineToken, '{{hyperline}}', $protected);
    }

    private function resolveLeadEmail(Lead $lead): ?string
    {
        foreach ([$lead->personal_email, $lead->company_email] as $email) {
            if ($this->isPresent($email)) {
                return trim((string) $email);
            }
        }

        return null;
    }

    private function isPresent(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }
        if (is_string($value) && trim($value) === '') {
            return false;
        }

        return true;
    }
}
