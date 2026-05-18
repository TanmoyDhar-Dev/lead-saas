<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Lead;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Builds and POSTs the email-automation webhook body expected by the existing n8n workflow.
 * Root keys and records[] keys must stay camelCase — do not rename to snake_case.
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

        $campaign->loadMissing(['campaignRecipients.lead', 'senderIdentity']);

        $payload = $this->buildRootPayload($campaign, $attachments);

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
        $sender = $campaign->senderIdentity;
        $searchWindow = $campaign->search_window;

        $records = [];
        foreach ($campaign->campaignRecipients as $recipient) {
            if ($recipient->lead instanceof Lead) {
                $records[] = $this->mapLeadToRecord($recipient->lead, $searchWindow);
            }
        }

        $deliveryMode = $campaign->delivery_mode === 'send' ? 'send' : 'draft';

        $signature = $campaign->email_signature;
        if ($signature === null || $signature === '') {
            $signature = $sender?->email_signature ?? '';
        }

        return [
            'action' => 'process_leads',
            'deliveryMode' => $deliveryMode,
            'records' => $records,
            'attachments' => array_values($attachments),
            'emailMainBody' => (string) $campaign->email_main_body,
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

    /**
     * @return array<string, mixed>
     */
    public function mapLeadToRecord(Lead $lead, ?string $searchWindow): array
    {
        $record = [
            'id' => $lead->id,
            'personName' => $lead->person_name,
            'companyName' => $lead->company_name,
            'industryApify' => $lead->industry_by_apifyapi,
            'companyWebsite' => $lead->company_website,
            'personalAddress' => $lead->personal_address_with_country,
        ];

        $optional = [
            'personalEmailAddress' => $lead->personal_email_address,
            'linkedinUrl' => $lead->personal__linkdin_url,
            'companyLinkedIn' => $lead->company_linkdin_url,
            'countryBySearchParam' => $lead->country_by_search_param,
            'cityBySearchParam' => $lead->city_by_search_param,
            'industrySearchParam' => $lead->industry_by_search_param,
            'positionSearchParam' => $lead->position_by_search_param,
            'positionApify' => $lead->position_by_apifiapi,
            'profileHeadline' => $lead->personal_linkdin_bio,
            'profileAbout' => $lead->personal_profile_about,
            'searchWindow' => $searchWindow,
        ];

        foreach ($optional as $key => $value) {
            if ($this->isPresent($value)) {
                $record[$key] = $value;
            }
        }

        return $record;
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
