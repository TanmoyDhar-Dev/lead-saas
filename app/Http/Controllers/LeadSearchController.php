<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeadSearchRequest;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Lead;
use App\Models\LeadSearch;
use App\Models\SenderIdentity;
use App\Services\N8nEmailProcessService;
use App\Services\N8nLeadCollectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LeadSearchController extends Controller
{
    public function index(Request $request)
    {
        $query = LeadSearch::visibleTo(auth()->user())->withCount('leads')->orderByDesc('created_at');
        
        // Filters
        if ($targetLocation = $request->input('target_location')) {
            $query->where('target_location', 'ilike', "%{$targetLocation}%");
        }
        if ($industry = $request->input('industry')) {
            $query->where('industry', 'ilike', "%{$industry}%");
        }
        if ($position = $request->input('position')) {
            $query->where('position', 'ilike', "%{$position}%");
        }
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }
        
        if (auth()->user()->isAdmin()) {
            if ($userId = $request->input('user_id')) {
                $query->where('user_id', $userId);
            }
            $query->with('user');
        }

        // Local search by query values
        if ($q = $request->input('q')) {
            $query->where(function ($query) use ($q) {
                $query->where('target_location', 'ilike', "%{$q}%")
                      ->orWhere('industry', 'ilike', "%{$q}%")
                      ->orWhere('position', 'ilike', "%{$q}%");
            });
        }
        
        $searches = $query->paginate(15)->withQueryString();
        
        if ($request->ajax()) {
            return view('lead-searches.partials.table', compact('searches'))->render();
        }
            
        return view('lead-searches.index', compact('searches'));
    }

    public function create()
    {
        return view('lead-searches.create');
    }

    public function store(Request $request, N8nLeadCollectionService $n8nService)
    {
        $validated = $request->validate([
            'target_location' => 'required|string|max:255',
            'industry' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'volume' => 'required|integer|min:1|max:100',
        ]);
        
        // Normalize fields to lowercase
        $validated['target_location'] = mb_strtolower(trim($validated['target_location']));
        if (!empty($validated['industry'])) {
            $validated['industry'] = mb_strtolower(trim($validated['industry']));
        }
        if (!empty($validated['position'])) {
            $validated['position'] = mb_strtolower(trim($validated['position']));
        }

$targetUserId = auth()->id();

        $leadSearch = LeadSearch::create([
            'user_id' => $targetUserId,
            'target_location' => $validated['target_location'],
            'industry' => $validated['industry'] ?? null,
            'position' => $validated['position'] ?? null,
            'volume' => $validated['volume'] ?? 10,
            'status' => 'processing', // Stays processing here!
            'started_at' => now(),
        ]);

        $validated['user_id'] = $targetUserId;
        $validated['lead_search_id'] = $leadSearch->id;

        // Trigger n8n webhook
        $response = $n8nService->searchLeads($validated);

        if ($response['successful']) {
            // DO NOT update status to completed here. n8n will do that later.
            return response()->json([
                'success' => true,
                'message' => 'Lead Hunter has started in the background. Leads will appear shortly.',
                'redirect' => route('lead-searches.index')
            ]);
        } else {
            $leadSearch->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_message' => $response['error'] ?? 'Failed to reach extraction server.',
            ]);

            return response()->json([
                'success' => false,
                'error' => "Lead search failed: " . ($response['error'] ?? 'Unknown error')
            ], 422);
        }
    }

    /**
     * View leads for a specific search query.
     */
    public function leads(Request $request, LeadSearch $leadSearch)
    {
        if (!auth()->user()->isAdmin() && $leadSearch->user_id !== auth()->id()) {
            abort(403);
        }

        // Do not run orphan backfill here: unbounded ILIKE + UPDATE across the leads table
        // can exceed PHP max_execution_time. Use `php artisan lead-search:attach-orphans {id}` if needed.

        $query = Lead::where('lead_search_id', $leadSearch->id);

        // Local filter/search
        if ($q = $request->input('q')) {
            $query->where(function ($query) use ($q) {
                $query->where('full_name', 'ilike', "%{$q}%")
                      ->orWhere('personal_email', 'ilike', "%{$q}%")
                      ->orWhere('company_email', 'ilike', "%{$q}%")
                      ->orWhere('company_name', 'ilike', "%{$q}%")
                      ->orWhere('linkedin_url', 'ilike', "%{$q}%")
                      ->orWhere('position', 'ilike', "%{$q}%");
            });
        }

        $user = $request->user();

        $leads = $query
            ->with(['campaignRecipients' => function ($recipientQuery) use ($user) {
                $recipientQuery
                    ->whereIn('status', ['sent', 'drafted'])
                    ->whereHas('campaign', fn ($campaignQuery) => $campaignQuery->where('user_id', $user->id))
                    ->orderByDesc('updated_at');
            }])
            ->orderBy('created_at', 'desc')
            ->paginate(25)
            ->withQueryString();

        $templateQuery = \App\Models\EmailTemplate::query();
        if (!auth()->user()->isAdmin()) {
            $templateQuery->where('user_id', auth()->id());
        }
        $templates = $templateQuery->get();

        if ($request->ajax()) {
            return view('lead-searches.partials.leads-table', compact('leads', 'leadSearch', 'templates'))->render();
        }

        return view('lead-searches.leads', compact('leadSearch', 'leads', 'templates'));
    }

    /**
     * Return lead details as JSON, scoped to a search.
     */
    public function leadJson(LeadSearch $leadSearch, Lead $lead)
    {
        if (!auth()->user()->isAdmin()) {
            if ($leadSearch->user_id !== auth()->id() || $lead->user_id !== auth()->id()) {
                abort(403, 'Unauthorized access to this lead.');
            }
        }

        if ($lead->lead_search_id !== $leadSearch->id) {
            abort(404, 'Lead not found for this search.');
        }

        // Exclude internal fields
        $hidden = ['id'];
        $data = collect($lead->toArray())->except($hidden)->toArray();

        // Add formatted dates
        $data['created_at_human'] = optional($lead->created_at)->format('M d, Y H:i') ?? 'N/A';
        $data['updated_at_human'] = optional($lead->updated_at)->format('M d, Y H:i') ?? 'N/A';

        return response()->json($data);
    }

    private function buildPreviewQuery(array $data): string
    {
        $positionBlock = '';
        if (!empty($data['position'])) {
            if (str_contains($data['position'], ' OR ')) {
                $positionBlock = "({$data['position']}) ";
            } else {
                $positionBlock = "(\"{$data['position']}\") ";
            }
        }

        $cityBlock = "(\"{$data['city']}\" OR \"{$data['city']} Bay Area\") ";
        $countryBlock = "\"{$data['country']}\" ";
        
        $industryBlock = '';
        if (!empty($data['industry'])) {
            $industryBlock = "(\"{$data['industry']}\")";
        }

        return trim("site:linkedin.com/in/ {$positionBlock}{$cityBlock}{$countryBlock}{$industryBlock}");
    }

    public function destroy(LeadSearch $leadSearch)
    {
        if (!auth()->user()->isAdmin() && $leadSearch->user_id !== auth()->id()) {
            abort(403);
        }

        // Associated leads should be deleted. 
        // If we want to be explicit instead of relying on cascade:
        $leadSearch->leads()->delete();
        $leadSearch->delete();

        return back()->with('success', 'Search record and associated leads deleted successfully.');
    }

    public function dispatchOutreach(Request $request, N8nEmailProcessService $n8nEmailService)
    {
        $validated = $request->validate([
            'lead_ids' => 'required|array|min:1',
            'lead_ids.*' => 'exists:leads,id',
            'delivery_mode' => 'required|string',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'sender_name' => 'nullable|string|max:255',
            'sender_role' => 'nullable|string|max:255',
            'sender_company' => 'nullable|string|max:255',
            'sender_address' => 'nullable|string|max:255',
            'attachments.*' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
        ]);

        $user = $request->user();
        $leads = Lead::visibleTo($user)->whereIn('id', $validated['lead_ids'])->get();

        if ($leads->count() !== count($validated['lead_ids'])) {
            return back()->withErrors(['lead_ids' => 'One or more selected leads are not accessible.']);
        }

        $attachmentPaths = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $attachmentPaths[] = [
                    'path' => $file->store('campaign-attachments', 'public'),
                    'name' => $file->getClientOriginalName(),
                    'contentType' => $file->getMimeType() ?: 'application/octet-stream',
                ];
            }
        }

        $deliveryMode = $validated['delivery_mode'] === 'Send Immediately' ? 'send' : 'draft';
        $senderIdentity = $this->resolveSenderIdentity($user->id, $validated);

        $campaign = DB::transaction(function () use ($user, $validated, $leads, $deliveryMode, $senderIdentity, $attachmentPaths) {
            $campaign = Campaign::create([
                'user_id' => $user->id,
                'sender_identity_id' => $senderIdentity?->id,
                'name' => 'Outreach '.now()->format('M d, Y H:i'),
                'delivery_mode' => $deliveryMode,
                'email_main_body' => $validated['body'],
                'status' => 'pending',
                'n8n_response' => [
                    'attachments' => $attachmentPaths,
                    'sender_address' => trim((string) ($validated['sender_address'] ?? '')),
                ],
            ]);

            foreach ($leads as $lead) {
                CampaignRecipient::create([
                    'campaign_id' => $campaign->id,
                    'lead_id' => $lead->id,
                    'tracking_id' => (string) Str::uuid(),
                    'status' => 'pending',
                    'subject' => $validated['subject'],
                ]);
            }

            return $campaign;
        });

        $result = $n8nEmailService->send($campaign->fresh(['campaignRecipients.lead', 'senderIdentity']));

        $campaign->update([
            'status' => match (true) {
                $result['successful'] => 'processing',
                $result['dispatched'] > 0 => 'partial',
                default => 'failed',
            },
            'error_message' => $result['failed'] > 0
                ? collect($result['results'])->pluck('error')->filter()->first()
                : null,
            'n8n_response' => array_merge(
                (array) ($campaign->n8n_response ?? []),
                ['dispatch' => $result],
            ),
        ]);

        if ($result['dispatched'] === 0) {
            $error = $result['results'][0]['error'] ?? 'Failed to dispatch campaign to n8n.';

            return back()->withErrors(['dispatch' => $error]);
        }

        $mode = $deliveryMode === 'send' ? 'Sending' : 'Drafting';
        $message = "Automation Sequence Initiated: {$mode} emails for {$result['dispatched']} leads.";

        if ($result['failed'] > 0) {
            $message .= " {$result['failed']} lead(s) could not be dispatched.";
        }

        return back()->with('success', $message);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function resolveSenderIdentity(int $userId, array $validated): ?SenderIdentity
    {
        $senderName = trim((string) ($validated['sender_name'] ?? ''));
        $senderRole = trim((string) ($validated['sender_role'] ?? ''));
        $senderCompany = trim((string) ($validated['sender_company'] ?? ''));

        if ($senderName === '' && $senderRole === '' && $senderCompany === '') {
            return SenderIdentity::where('user_id', $userId)->where('is_default', true)->first()
                ?? SenderIdentity::where('user_id', $userId)->first();
        }

        $existing = SenderIdentity::query()
            ->where('user_id', $userId)
            ->where('sender_name', $senderName)
            ->where('sender_role', $senderRole ?: null)
            ->where('sender_company', $senderCompany ?: null)
            ->first();

        if ($existing) {
            return $existing;
        }

        $isFirstForUser = SenderIdentity::where('user_id', $userId)->doesntExist();

        $identity = SenderIdentity::create([
            'user_id' => $userId,
            'sender_name' => $senderName,
            'sender_role' => $senderRole ?: null,
            'sender_company' => $senderCompany ?: null,
            'name' => $senderName !== '' ? $senderName : 'Outreach Sender',
        ]);

        if ($isFirstForUser) {
            DB::table('sender_identities')
                ->where('id', $identity->id)
                ->update(['is_default' => DB::raw('true')]);
            $identity->refresh();
        }

        return $identity;
    }
}
