<?php

namespace App\Http\Controllers;

use App\Models\CampaignRecipient;
use App\Models\ImportedOutreachRecipient;
use App\Support\EmailTracking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class TrackingController extends Controller
{
    private const TRANSPARENT_GIF = 'R0lGODlhAQABAJAAAP8AAAAAACH5BAUQAAAALAAAAAABAAEAAAICBAEAOw==';

    public function open(Request $request, string $tracking_id): Response
    {
        $trackingId = EmailTracking::normalizeTrackingId($tracking_id);

        if ($trackingId !== '') {
            $this->recordOpen($trackingId);
        }

        $gif = base64_decode(self::TRANSPARENT_GIF) ?: '';

        return response($gif, 200, [
            'Content-Type' => 'image/gif',
            'Content-Length' => (string) strlen($gif),
            'Cache-Control' => 'no-cache, no-store, must-revalidate, private',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    public function click(Request $request, string $tracking_id): RedirectResponse
    {
        $trackingId = EmailTracking::normalizeTrackingId($tracking_id);
        $url = $request->query('url', '/');

        if ($trackingId !== '') {
            $recipient = CampaignRecipient::query()
                ->where('tracking_id', $trackingId)
                ->first();

            if ($recipient) {
                if ($recipient->clicked_at === null) {
                    $recipient->update(['clicked_at' => now()]);
                }
            } else {
                $importedRecipient = ImportedOutreachRecipient::query()
                    ->where('tracking_id', $trackingId)
                    ->first();

                if ($importedRecipient && $importedRecipient->clicked_at === null) {
                    $importedRecipient->update(['clicked_at' => now()]);
                }
            }
        }

        return redirect()->away(is_string($url) && $url !== '' ? $url : '/');
    }

    private function recordOpen(string $trackingId): void
    {
        if ($this->bumpOpen(CampaignRecipient::class, $trackingId)) {
            return;
        }

        if (! $this->bumpOpen(ImportedOutreachRecipient::class, $trackingId)) {
            Log::info('Open tracking pixel hit with unknown tracking_id.', [
                'tracking_id' => $trackingId,
            ]);
        }
    }

    /**
     * @param  class-string  $model
     */
    private function bumpOpen(string $model, string $trackingId): bool
    {
        $query = $model::query()->where('tracking_id', $trackingId);

        if (! $query->exists()) {
            return false;
        }

        $model::query()
            ->where('tracking_id', $trackingId)
            ->whereNull('opened_at')
            ->update(['opened_at' => now()]);

        $model::query()
            ->where('tracking_id', $trackingId)
            ->increment('open_count');

        return true;
    }
}
