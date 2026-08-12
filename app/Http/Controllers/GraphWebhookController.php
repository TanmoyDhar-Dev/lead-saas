<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessInboundGraphEmailJob;
use App\Models\GraphSubscription;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class GraphWebhookController extends Controller
{
    /**
     * Receive Microsoft Graph change notifications for inbound mail.
     *
     * Must complete validation handshakes and return 202 quickly (< 3s)
     * without fetching message bodies in-request.
     */
    public function __invoke(Request $request): Response
    {
        // 1) Validation handshake — return token immediately as text/plain.
        if ($request->filled('validationToken')) {
            return response((string) $request->query('validationToken'), 200)
                ->header('Content-Type', 'text/plain');
        }

        // 2) Payload processing — extract notification batch.
        $notifications = $request->input('value', []);

        if (! is_array($notifications)) {
            return response()->noContent(202);
        }

        // 3) Security check + 4) queue dispatch for valid notifications only.
        foreach ($notifications as $notification) {
            if (! is_array($notification)) {
                continue;
            }

            $clientState = (string) ($notification['clientState'] ?? '');

            if ($clientState === '') {
                Log::warning('Graph webhook notification missing clientState.', [
                    'subscriptionId' => $notification['subscriptionId'] ?? null,
                ]);
                continue;
            }

            $subscriptionExists = GraphSubscription::query()
                ->where('client_state', $clientState)
                ->exists();

            if (! $subscriptionExists) {
                Log::warning('Graph webhook notification rejected: clientState mismatch.', [
                    'subscriptionId' => $notification['subscriptionId'] ?? null,
                ]);
                continue;
            }

            ProcessInboundGraphEmailJob::dispatch($notification);
        }

        // 5) Acknowledge immediately — do not process email content here.
        return response()->noContent(202);
    }
}
