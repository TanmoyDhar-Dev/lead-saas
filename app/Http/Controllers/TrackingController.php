<?php

namespace App\Http\Controllers;

use App\Models\CampaignRecipient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TrackingController extends Controller
{
    private const TRANSPARENT_GIF = 'R0lGODlhAQABAJAAAP8AAAAAACH5BAUQAAAALAAAAAABAAEAAAICBAEAOw==';

    public function open(Request $request, string $tracking_id): Response
    {
        $recipient = CampaignRecipient::query()
            ->where('tracking_id', $tracking_id)
            ->first();

        if ($recipient) {
            if ($recipient->opened_at === null) {
                $recipient->opened_at = now();
            }

            $recipient->open_count = (int) $recipient->open_count + 1;
            $recipient->save();
        }

        return response(base64_decode(self::TRANSPARENT_GIF), 200, [
            'Content-Type' => 'image/gif',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    public function click(Request $request, string $tracking_id): RedirectResponse
    {
        $url = $request->query('url', '/');

        $recipient = CampaignRecipient::query()
            ->where('tracking_id', $tracking_id)
            ->first();

        if ($recipient && $recipient->clicked_at === null) {
            $recipient->update(['clicked_at' => now()]);
        }

        return redirect()->away($url);
    }
}
