<?php

namespace App\Services;

use App\Models\ConnectedMailbox;
use App\Models\GraphSubscription;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class GraphSubscriptionService
{
    /**
     * Ensure the user has an active inbox message subscription for reply webhooks.
     */
    public function ensureInboxSubscription(User $user, ?string $notificationUrl = null): GraphSubscription
    {
        $existing = GraphSubscription::query()
            ->where('user_id', $user->id)
            ->where('expiration_date', '>', now()->addHour())
            ->orderByDesc('expiration_date')
            ->first();

        if ($existing) {
            return $existing;
        }

        return $this->createInboxSubscription($user, $notificationUrl);
    }

    public function createInboxSubscription(User $user, ?string $notificationUrl = null): GraphSubscription
    {
        $mailbox = ConnectedMailbox::query()
            ->where('user_id', $user->id)
            ->where('provider', 'microsoft')
            ->first();

        if ($mailbox === null) {
            throw new RuntimeException('No connected Microsoft mailbox found.');
        }

        $webhookUrl = $this->resolveWebhookUrl($notificationUrl);
        if (! str_starts_with($webhookUrl, 'https://')) {
            throw new RuntimeException(
                'Graph webhook URL must be public HTTPS (set GRAPH_WEBHOOK_URL to your ngrok URL). Got: '.$webhookUrl
            );
        }

        $accessToken = ConnectedMailbox::getFreshAccessToken($mailbox);
        $clientState = Str::random(48);
        $expiration = now()->addDays(2)->utc();
        $resource = "me/mailFolders('inbox')/messages";

        $response = Http::withoutVerifying()
            ->withToken($accessToken)
            ->acceptJson()
            ->asJson()
            ->timeout(30)
            ->post('https://graph.microsoft.com/v1.0/subscriptions', [
                'changeType' => 'created',
                'notificationUrl' => $webhookUrl,
                'resource' => $resource,
                'expirationDateTime' => $expiration->format('Y-m-d\TH:i:s.u\Z'),
                'clientState' => $clientState,
            ]);

        if (! $response->successful()) {
            $error = $response->json('error.message') ?? $response->body();
            Log::error('Failed creating Graph inbox subscription.', [
                'user_id' => $user->id,
                'status' => $response->status(),
                'error' => $error,
                'webhook_url' => $webhookUrl,
            ]);

            throw new RuntimeException(
                is_string($error) ? $error : 'Failed to create Microsoft Graph subscription.'
            );
        }

        $payload = $response->json();
        $subscriptionId = is_array($payload) ? ($payload['id'] ?? null) : null;
        $expirationFromGraph = is_array($payload) ? ($payload['expirationDateTime'] ?? null) : null;

        if (! is_string($subscriptionId) || $subscriptionId === '') {
            throw new RuntimeException('Graph subscription created but no subscription id was returned.');
        }

        // Replace any stale local rows for this user.
        GraphSubscription::query()->where('user_id', $user->id)->delete();

        return GraphSubscription::create([
            'user_id' => $user->id,
            'subscription_id' => $subscriptionId,
            'resource' => is_array($payload) ? ((string) ($payload['resource'] ?? $resource)) : $resource,
            'client_state' => $clientState,
            'expiration_date' => is_string($expirationFromGraph) && $expirationFromGraph !== ''
                ? \Illuminate\Support\Carbon::parse($expirationFromGraph)
                : $expiration,
        ]);
    }

    public function resolveWebhookUrl(?string $override = null): string
    {
        $url = trim((string) ($override ?: config('services.azure.graph_webhook_url')));

        if ($url === '') {
            $url = rtrim((string) config('app.url'), '/').'/webhooks/graph/notifications';
        }

        return $url;
    }

    /**
     * Best-effort ensure; logs instead of throwing (safe for OAuth/outreach hooks).
     */
    public function tryEnsureInboxSubscription(User $user): ?GraphSubscription
    {
        try {
            return $this->ensureInboxSubscription($user);
        } catch (Throwable $e) {
            Log::warning('Could not ensure Graph inbox subscription.', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
