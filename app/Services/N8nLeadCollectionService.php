<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;
use Throwable;

class N8nLeadCollectionService
{
    public function searchLeads(array $data): array
    {
        $webhookUrl = config('services.n8n.lead_search_webhook_url');
        $timeout = config('services.n8n.timeout', 300);

        if (!$webhookUrl) {
            return [
                'successful' => false,
                'status' => null,
                'body' => null,
                'error' => 'n8n webhook URL is not configured.',
            ];
        }

        try {
            $response = Http::timeout($timeout)
                ->retry(2, 2000)
                ->post($webhookUrl, [
                    'country'        => $data['country'],
                    'city'           => $data['city'],
                    'industry'       => $data['industry'] ?? null,
                    'position'       => $data['position'] ?? null,
                    'volume'         => $data['volume'] ?? 10,
                    'user_id'        => $data['user_id'] ?? null,
                    'lead_search_id' => $data['lead_search_id'] ?? null,
                ]);

            return [
                'successful' => $response->successful(),
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
                'error' => $response->successful() ? null : 'Received non-success HTTP status code.',
            ];

        } catch (ConnectionException $e) {
            return [
                'successful' => false,
                'status' => null,
                'body' => null,
                'error' => 'Connection timeout or error: ' . $e->getMessage(),
            ];
        } catch (Throwable $e) {
            return [
                'successful' => false,
                'status' => null,
                'body' => null,
                'error' => 'Unexpected error occurred: ' . $e->getMessage(),
            ];
        }
    }
}
