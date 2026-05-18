<?php

namespace App\Http\Controllers;

use App\Models\ConnectedMailbox;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Throwable;

class MatonConnectionController extends Controller
{
    public function createConnection(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'provider' => ['required', 'string', Rule::in([
                ConnectedMailbox::PROVIDER_GOOGLE_MAIL,
                ConnectedMailbox::PROVIDER_OUTLOOK,
            ])],
        ]);

        $apiKey = config('services.maton.api_key');
        if (! $apiKey) {
            return response()->json([
                'message' => 'Maton API key is not configured.',
            ], 500);
        }

        $baseUrl = rtrim(config('services.maton.base_url', 'https://api.maton.ai'), '/');

        try {
            $response = Http::timeout(20)
                ->acceptJson()
                ->withToken($apiKey)
                ->post($baseUrl . '/connections', [
                    'app' => $validated['provider'],
                ]);

            if (! $response->successful()) {
                Log::warning('Maton connection creation failed.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'user_id' => $request->user()->id,
                ]);

                return response()->json([
                    'message' => 'Failed to create connection with email provider.',
                ], 502);
            }

            $payload = $response->json() ?? [];
            $connectionId = $this->extractConnectionId($payload);
            $authUrl = $this->extractAuthUrl($response, $payload, $connectionId);

            if (! $authUrl && $connectionId) {
                $authUrl = $this->fetchConnectionUrl($baseUrl, $apiKey, $connectionId);
            }

            if (! $connectionId || ! $authUrl) {
                Log::warning('Maton connection response missing required fields.', [
                    'body' => $payload,
                    'headers' => $response->headers(),
                    'user_id' => $request->user()->id,
                ]);

                return response()->json([
                    'message' => 'Invalid response received from Maton.',
                ], 502);
            }

            ConnectedMailbox::updateOrCreate(
                [
                    'user_id' => $request->user()->id,
                    'provider' => $validated['provider'],
                ],
                [
                    'email_address' => $payload['email_address'] ?? null,
                    'maton_connection_id' => $connectionId,
                    'status' => ConnectedMailbox::STATUS_ACTIVE,
                ]
            );

            return response()->json([
                'auth_url' => $authUrl,
            ]);
        } catch (Throwable $e) {
            Log::error('Maton createConnection exception.', [
                'message' => $e->getMessage(),
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Unable to create mailbox connection at this time.',
            ], 500);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function extractConnectionId(array $payload): ?string
    {
        return $payload['connection_id']
            ?? $payload['connectionId']
            ?? data_get($payload, 'data.connection_id')
            ?? data_get($payload, 'data.connectionId')
            ?? data_get($payload, 'connection.id');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function extractAuthUrl($response, array $payload, ?string $connectionId): ?string
    {
        $authUrl = $payload['auth_url']
            ?? $payload['authUrl']
            ?? $payload['url']
            ?? data_get($payload, 'data.auth_url')
            ?? data_get($payload, 'data.authUrl')
            ?? data_get($payload, 'data.url')
            ?? $response->header('Location')
            ?? data_get($payload, 'redirect_url');

        if ($authUrl || ! $connectionId) {
            return $authUrl;
        }

        return null;
    }

    private function fetchConnectionUrl(string $baseUrl, string $apiKey, string $connectionId): ?string
    {
        try {
            $response = Http::timeout(20)
                ->acceptJson()
                ->withToken($apiKey)
                ->get($baseUrl . '/connections/' . $connectionId);

            if (! $response->successful()) {
                Log::warning('Unable to fetch Maton connection detail.', [
                    'status' => $response->status(),
                    'connection_id' => $connectionId,
                ]);

                return null;
            }

            $payload = $response->json() ?? [];

            return data_get($payload, 'connection.url')
                ?? data_get($payload, 'url')
                ?? data_get($payload, 'data.url')
                ?? data_get($payload, 'connection.auth_url')
                ?? data_get($payload, 'auth_url');
        } catch (Throwable $e) {
            Log::warning('Exception while fetching Maton connection detail.', [
                'message' => $e->getMessage(),
                'connection_id' => $connectionId,
            ]);

            return null;
        }
    }
}
