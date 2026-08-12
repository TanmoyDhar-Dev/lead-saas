<?php

namespace App\Console\Commands;

use App\Models\ConnectedMailbox;
use App\Models\GraphSubscription;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class RenewGraphSubscriptionsCommand extends Command
{
    protected $signature = 'graph:renew-subscriptions';

    protected $description = 'Renew Microsoft Graph webhook subscriptions that expire within the next 24 hours.';

    public function handle(): int
    {
        $threshold = now()->addHours(24);

        $subscriptions = GraphSubscription::query()
            ->where('expiration_date', '<=', $threshold)
            ->orderBy('expiration_date')
            ->get();

        if ($subscriptions->isEmpty()) {
            $this->info('No Graph subscriptions need renewal.');

            return self::SUCCESS;
        }

        $this->info("Found {$subscriptions->count()} subscription(s) to renew.");

        $renewed = 0;
        $deleted = 0;
        $failed = 0;

        foreach ($subscriptions as $subscription) {
            try {
                $result = $this->renewSubscription($subscription);

                if ($result === 'renewed') {
                    $renewed++;
                } elseif ($result === 'deleted') {
                    $deleted++;
                } else {
                    $failed++;
                }
            } catch (Throwable $e) {
                $failed++;
                Log::error('Unexpected error renewing Graph subscription.', [
                    'subscription_id' => $subscription->subscription_id,
                    'user_id' => $subscription->user_id,
                    'error' => $e->getMessage(),
                ]);
                $this->error("Failed renewing {$subscription->subscription_id}: {$e->getMessage()}");
            }
        }

        $this->info("Done. Renewed: {$renewed}, deleted: {$deleted}, failed: {$failed}.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function renewSubscription(GraphSubscription $subscription): string
    {
        $mailbox = ConnectedMailbox::query()
            ->where('user_id', $subscription->user_id)
            ->where('provider', 'microsoft')
            ->first();

        if ($mailbox === null) {
            Log::warning('Skipping Graph subscription renewal: no Microsoft mailbox.', [
                'subscription_id' => $subscription->subscription_id,
                'user_id' => $subscription->user_id,
            ]);
            $this->warn("No mailbox for user {$subscription->user_id}; skipped {$subscription->subscription_id}.");

            return 'failed';
        }

        $accessToken = ConnectedMailbox::getFreshAccessToken($mailbox);
        $newExpiration = now()->addHours(48)->utc();

        $response = Http::withoutVerifying()
            ->withToken($accessToken)
            ->acceptJson()
            ->asJson()
            ->timeout(30)
            ->patch(
                'https://graph.microsoft.com/v1.0/subscriptions/'.$subscription->subscription_id,
                [
                    'expirationDateTime' => $newExpiration->format('Y-m-d\TH:i:s.u\Z'),
                ]
            );

        if ($response->status() === 404) {
            Log::warning('Graph subscription not found at Microsoft; deleting local record.', [
                'subscription_id' => $subscription->subscription_id,
                'user_id' => $subscription->user_id,
            ]);
            $subscription->delete();
            $this->warn("Deleted local subscription {$subscription->subscription_id} (Graph 404).");

            return 'deleted';
        }

        if (! $response->successful()) {
            Log::error('Graph subscription renewal failed.', [
                'subscription_id' => $subscription->subscription_id,
                'user_id' => $subscription->user_id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            $this->error("Renewal failed for {$subscription->subscription_id} (HTTP {$response->status()}).");

            return 'failed';
        }

        $payload = $response->json();
        $expirationFromGraph = is_array($payload) ? ($payload['expirationDateTime'] ?? null) : null;

        $subscription->update([
            'expiration_date' => is_string($expirationFromGraph) && $expirationFromGraph !== ''
                ? Carbon::parse($expirationFromGraph)
                : $newExpiration,
        ]);

        $this->line("Renewed {$subscription->subscription_id} until {$subscription->fresh()->expiration_date}.");

        return 'renewed';
    }
}
