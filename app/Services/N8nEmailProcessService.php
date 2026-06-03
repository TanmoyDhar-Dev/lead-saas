<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\ConnectedMailbox;
use App\Models\Lead;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Builds and POSTs the email-automation webhook body expected by the n8n workflow.
 * Root keys use camelCase for n8n compatibility.
 */
class N8nEmailProcessService
{
    /**
     * @param  array<int, mixed>  $attachments
     * @return array{successful: bool, status: ?int, body: mixed, error: ?string}
     */
    public function send(Campaign $campaign, array $attachments = []): array
    {
        $webhookUrl = config('services.n8n.email_process_webhook_url');
        $timeout = (int) config('services.n8n.timeout', 300);

        if (! $webhookUrl) {
            return [
                'successful' => false,
                'status' => null,
                'body' => null,
                'error' => 'n8n email process webhook URL is not configured (N8N_EMAIL_PROCESS_WEBHOOK_URL).',
            ];
        }

        try {
            $payload = $this->buildRootPayload($campaign, $attachments);
        } catch (Throwable $e) {
            return [
                'successful' => false,
                'status' => null,
                'body' => null,
                'error' => $e->getMessage(),
            ];
        }

        try {
            $response = Http::timeout($timeout)
                ->retry(2, 2000)
                ->asJson()
                ->post($webhookUrl, $payload);

            return [
                'successful' => $response->successful(),
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
                'error' => $response->successful() ? null : 'Received non-success HTTP status from n8n email webhook.',
            ];
        } catch (Throwable $e) {
            return [
                'successful' => false,
                'status' => null,
                'body' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * @param  array<int, mixed>  $attachments
     * @return array<string, mixed>
     */
    public function buildRootPayload(Campaign $campaign, array $attachments = []): array
    {
        $campaign->loadMissing(['campaignRecipients.lead', 'senderIdentity', 'user']);

        $mailbox = ConnectedMailbox::where('user_id', $campaign->user_id)
            ->where('provider', 'microsoft')
            ->first();

        if (! $mailbox) {
            throw new \RuntimeException('No connected Microsoft mailbox found for this user. Connect Outlook first.');
        }

        $accessToken = ConnectedMailbox::getFreshAccessToken($mailbox);

        $sender = $campaign->senderIdentity;
        $searchWindow = $campaign->search_window;
        $records = [];

        foreach ($campaign->campaignRecipients as $recipient) {
            if (! $recipient->lead instanceof Lead) {
                continue;
            }

            $email = $this->resolveLeadEmail($recipient->lead);
            if ($email === null) {
                continue;
            }

            if ($recipient->tracking_id === null) {
                $recipient->tracking_id = (string) Str::uuid();
                $recipient->save();
            }

            $records[] = [
                'email' => $email,
                'tracking_id' => $recipient->tracking_id,
                ...$this->mapLeadToRecord($recipient->lead, $searchWindow),
            ];
        }

        $emailMainBody = $this->rewriteLinksForTracking((string) $campaign->email_main_body);

        $deliveryMode = $campaign->delivery_mode === 'send' ? 'send' : 'draft';

        $signature = $campaign->email_signature;
        if ($signature === null || $signature === '') {
            $signature = $sender?->email_signature ?? '';
        }

        return [
            'action' => 'process_leads',
            'deliveryMode' => $deliveryMode,
            'microsoft_access_token' => $accessToken,
            'emailMainBody' => $emailMainBody,
            'records' => $records,
            'attachments' => array_values($attachments),
            'emailSignature' => (string) $signature,
            'senderName' => (string) ($sender?->sender_name ?? ''),
            'senderRole' => (string) ($sender?->sender_role ?? ''),
            'senderCompany' => (string) ($sender?->sender_company ?? ''),
            'senderRegion' => (string) ($sender?->sender_region ?? ''),
            'senderIndustry' => (string) ($sender?->sender_industry ?? ''),
            'senderLinkedIn' => (string) ($sender?->sender_linkedin ?? ''),
            'senderWebsite' => (string) ($sender?->sender_website ?? ''),
            'senderEoChapter' => (string) ($sender?->sender_eo_chapter ?? ''),
        ];
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

    /**
     * @return array<string, mixed>
     */
    public function mapLeadToRecord(Lead $lead, ?string $searchWindow): array
    {
        $record = [
            'id' => $lead->id,
            'personName' => $lead->full_name,
            'companyName' => $lead->company_name,
            'industryApify' => $lead->industry,
            'companyWebsite' => $lead->company_website,
            'personalAddress' => $lead->address,
        ];

        $optional = [
            'personalEmailAddress' => $lead->personal_email,
            'linkedinUrl' => $lead->linkedin_url,
            'companyLinkedIn' => $lead->company_linkedin,
            'countryBySearchParam' => $lead->company_country,
            'cityBySearchParam' => $lead->company_city,
            'profileHeadline' => $lead->job_title,
            'profileAbout' => $lead->bio,
            'searchWindow' => $searchWindow,
        ];

        foreach ($optional as $key => $value) {
            if ($this->isPresent($value)) {
                $record[$key] = $value;
            }
        }

        return $record;
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
