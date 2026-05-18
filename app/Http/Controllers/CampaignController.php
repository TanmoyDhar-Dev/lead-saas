<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Lead;
use App\Models\SenderIdentity;
use App\Models\EmailBodyTemplate;
use App\Models\User;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    public function index(Request $request)
    {
        $query = Campaign::visibleTo(auth()->user())->withCount('campaignRecipients')->orderByDesc('created_at');

        if (auth()->user()->isAdmin()) {
            $query->with('user');
        }

        if (auth()->user()->isAdmin() && $request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $campaigns = $query->paginate(20)->withQueryString();

        return view('campaigns.index', compact('campaigns'));
    }

    public function create(Request $request)
    {
        $targetUserId = auth()->id();
        if (auth()->user()->isAdmin() && $request->filled('user_id')) {
            $targetUserId = $request->user_id;
        }

        $leads = Lead::where('user_id', $targetUserId)->get();
        $senders = SenderIdentity::where('user_id', $targetUserId)->get();
        $templates = EmailBodyTemplate::where('user_id', $targetUserId)->get();
        $users = auth()->user()->isAdmin() ? User::all() : null;

        return view('campaigns.create', compact('leads', 'senders', 'templates', 'users', 'targetUserId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'lead_ids' => 'required|array|min:1',
            'lead_ids.*' => 'exists:leads,id',
            'user_id' => 'sometimes|exists:users,id',
        ]);

        $targetUserId = auth()->user()->isAdmin() && ! empty($validated['user_id'])
            ? (int) $validated['user_id']
            : (int) auth()->id();

        $ownedLeadsCount = Lead::whereIn('id', $validated['lead_ids'])
            ->where('user_id', $targetUserId)
            ->count();

        if ($ownedLeadsCount !== count($validated['lead_ids'])) {
            abort(403, 'One or more leads are not owned by the selected user.');
        }

        $campaign = Campaign::create([
            'user_id' => $targetUserId,
            'sender_identity_id' => null,
            'name' => $validated['name'],
            'delivery_mode' => 'draft',
            'search_window' => 'qdr:m3',
            'email_main_body' => '',
            'email_signature' => null,
            'status' => 'draft',
        ]);

        foreach ($validated['lead_ids'] as $leadId) {
            $campaign->campaignRecipients()->create([
                'lead_id' => $leadId,
                'status' => 'pending',
            ]);
        }

        return redirect()->route('campaigns.confirm', $campaign)
            ->with('success', 'Campaign created. Review and initiate automation when ready.');
    }

    public function show(Campaign $campaign)
    {
        $campaign->load(['campaignRecipients.lead', 'senderIdentity']);

        $this->authorize('view', $campaign);

        return view('campaigns.show', compact('campaign'));
    }

}
