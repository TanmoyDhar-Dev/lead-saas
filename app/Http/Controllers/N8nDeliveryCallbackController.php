<?php

namespace App\Http\Controllers;

use App\Models\CampaignRecipient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class N8nDeliveryCallbackController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'campaign_recipient_id' => 'required|uuid|exists:campaign_recipients,id',
            'status' => 'required|string|max:255',
            'hyper_personalized_line' => 'nullable|string',
            'news_summary' => 'nullable|string',
            'sent_at' => 'nullable|date',
            'drafted_at' => 'nullable|date',
        ]);

        $recipient = CampaignRecipient::findOrFail($validated['campaign_recipient_id']);

        $recipient->update([
            'status' => $validated['status'],
            'hyper_personalized_line' => $validated['hyper_personalized_line'] ?? null,
            'news_summary' => $validated['news_summary'] ?? null,
            'sent_at' => isset($validated['sent_at'])
                ? Carbon::parse($validated['sent_at'])
                : null,
            'drafted_at' => isset($validated['drafted_at'])
                ? Carbon::parse($validated['drafted_at'])
                : null,
        ]);

        return response()->json([
            'message' => 'Campaign recipient updated successfully.',
            'campaign_recipient_id' => $recipient->id,
        ]);
    }
}
