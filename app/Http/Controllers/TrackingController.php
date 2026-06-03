<?php

namespace App\Http\Controllers;

use App\Models\CampaignRecipient;
use App\Models\EmailTrackingLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class TrackingController extends Controller
{
    private const TRANSPARENT_GIF = 'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

    public function open(Request $request, string $trackingId): Response
    {
        $recipient = CampaignRecipient::where('tracking_id', $trackingId)->first();

        if ($recipient) {
            EmailTrackingLog::create([
                'campaign_recipient_id' => $recipient->id,
                'event_type' => 'open',
                'ip_address' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 1000),
            ]);

            if ($recipient->opened_at === null) {
                $recipient->update(['opened_at' => now()]);
            }
        }

        return response(base64_decode(self::TRANSPARENT_GIF), 200)
            ->header('Content-Type', 'image/gif')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
    }

    public function click(Request $request, string $trackingId): RedirectResponse
    {
        $url = $request->query('url', '/');

        $recipient = CampaignRecipient::where('tracking_id', $trackingId)->first();

        if ($recipient) {
            EmailTrackingLog::create([
                'campaign_recipient_id' => $recipient->id,
                'event_type' => 'click',
                'clicked_url' => $url,
                'ip_address' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 1000),
            ]);

            if ($recipient->clicked_at === null) {
                $recipient->update(['clicked_at' => now()]);
            }
        }

        return redirect()->away($url);
    }
}
