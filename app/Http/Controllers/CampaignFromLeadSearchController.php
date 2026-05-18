<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Lead;
use App\Models\LeadSearch;
use Illuminate\Http\Request;

class CampaignFromLeadSearchController extends Controller
{
    /**
     * Create a campaign and recipients from a lead-search extraction selection, then go to confirmation.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'lead_search_id' => 'required|exists:lead_searches,id',
            'select_all_leads' => 'sometimes|boolean',
            'selected_lead_ids' => 'required_unless:select_all_leads,1|array|min:1',
            'selected_lead_ids.*' => 'exists:leads,id',
        ]);

        $leadSearch = LeadSearch::query()->findOrFail($validated['lead_search_id']);

        if (! auth()->user()->isAdmin() && (int) $leadSearch->user_id !== (int) auth()->id()) {
            abort(403, 'Unauthorized access to lead search.');
        }

        $leadIds = $request->boolean('select_all_leads')
            ? Lead::query()->where('lead_search_id', $leadSearch->id)->pluck('id')->all()
            : $validated['selected_lead_ids'];

        if (empty($leadIds)) {
            return back()->with('error', 'No leads selected.');
        }

        // Validate that all selected leads belong to the current leadSearch
        $validInSearchCount = Lead::query()->whereIn('id', $leadIds)
            ->where('lead_search_id', $leadSearch->id)
            ->count();

        if ($validInSearchCount !== count($leadIds)) {
            abort(403, 'One or more leads do not belong to this extraction.');
        }

        // Normal user can only select leads they own (leads.user_id = auth()->id())
        if (! auth()->user()->isAdmin()) {
            $ownedCount = Lead::query()->whereIn('id', $leadIds)
                ->where('user_id', auth()->id())
                ->count();

            if ($ownedCount !== count($leadIds)) {
                abort(403, 'You cannot include leads owned by another user.');
            }
        }

        $campaignOwnerId = (int) ($leadSearch->user_id ?: auth()->id());

        $name = sprintf(
            'Extraction — %s, %s — %s',
            $leadSearch->city ?? '',
            $leadSearch->country ?? '',
            now()->format('Y-m-d H:i')
        );

        $campaign = Campaign::query()->create([
            'user_id' => $campaignOwnerId,
            'sender_identity_id' => null,
            'name' => $name,
            'delivery_mode' => 'draft',
            'search_window' => 'qdr:m3',
            'email_main_body' => '',
            'email_signature' => null,
            'status' => 'draft',
        ]);

        foreach ($leadIds as $leadId) {
            $campaign->campaignRecipients()->create([
                'lead_id' => $leadId,
                'status' => 'pending',
            ]);
        }

        return redirect()->route('campaigns.confirm', $campaign)
            ->with('success', 'Campaign created. Review settings before starting automation.');
    }
}
