<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\GraphSubscriptionService;
use Illuminate\Console\Command;
use Throwable;

class SubscribeGraphInboxCommand extends Command
{
    protected $signature = 'graph:subscribe-inbox
                            {--user= : User ID that owns the connected Outlook mailbox}
                            {--url= : Override notification URL (ngrok HTTPS endpoint)}';

    protected $description = 'Create a Microsoft Graph inbox subscription for inbound reply webhooks.';

    public function handle(GraphSubscriptionService $subscriptions): int
    {
        $userId = $this->option('user') ?: auth()->id();

        if (! $userId) {
            $userId = $this->ask('User ID');
        }

        $user = User::query()->find($userId);
        if (! $user) {
            $this->error('User not found.');

            return self::FAILURE;
        }

        try {
            $subscription = $subscriptions->createInboxSubscription(
                $user,
                $this->option('url') ?: null
            );
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Graph inbox subscription created.');
        $this->line('subscription_id: '.$subscription->subscription_id);
        $this->line('expires: '.$subscription->expiration_date);
        $this->line('webhook: '.$subscriptions->resolveWebhookUrl($this->option('url') ?: null));

        return self::SUCCESS;
    }
}
