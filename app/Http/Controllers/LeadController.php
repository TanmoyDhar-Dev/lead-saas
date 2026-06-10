<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        $query = Lead::visibleTo(auth()->user());

        if (auth()->user()->isAdmin()) {
            if ($userId = $request->input('user_id')) {
                $query->where('user_id', $userId);
            }
            $query->with('user');
        }

        // Local vs Scraped filter
        if ($filterType = $request->input('type')) {
            if ($filterType === 'local') {
                $query->where('source', '!=', 'n8n_search');
            } elseif ($filterType === 'scraped') {
                $query->where('source', 'n8n_search');
            }
        }

        // Search
        if ($q = $request->input('q')) {
            $query->where(function ($query) use ($q) {
                $query->where('person_name', 'ilike', "%{$q}%")
                      ->orWhere('personal_email_address', 'ilike', "%{$q}%")
                      ->orWhere('company_name', 'ilike', "%{$q}%")
                      ->orWhere('personal__linkdin_url', 'ilike', "%{$q}%")
                      ->orWhere('company_website', 'ilike', "%{$q}%");
            });
        }

        // Filters
        if ($country = $request->input('country')) {
            $query->where('country_by_search_param', 'ilike', "%{$country}%");
        }

        if ($city = $request->input('city')) {
            $query->where('city_by_search_param', 'ilike', "%{$city}%");
        }

        if ($industry = $request->input('industry')) {
            $query->where(function ($query) use ($industry) {
                $query->where('industry_by_search_param', 'ilike', "%{$industry}%")
                      ->orWhere('industry_by_apifyapi', 'ilike', "%{$industry}%");
            });
        }

        if ($emailStatus = $request->input('email_status')) {
            $query->where('email_sent', $emailStatus);
        }

        if ($source = $request->input('source')) {
            $query->where('source', $source);
        }

        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        // Sorting
        $sort = $request->input('sort', 'created_at');
        $direction = $request->input('direction', 'desc');
        
        $allowedSorts = ['created_at', 'person_name', 'company_name', 'email_sent'];
        if (!in_array($sort, $allowedSorts)) {
            $sort = 'created_at';
        }
        $direction = strtolower($direction) === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sort, $direction);

        $leads = $query->paginate(25)->withQueryString();

        if ($request->ajax()) {
            return view('leads.partials.table', compact('leads'))->render();
        }

        return view('leads.index', compact('leads'));
    }

    public function show($id)
    {
        $lead = Lead::where('id', $id)
            ->firstOrFail();

        $this->authorize('view', $lead);

        return view('leads.show', compact('lead'));
    }

    public function destroy($id)
    {
        $lead = Lead::findOrFail($id);
        $this->authorize('delete', $lead);
        $lead->delete();

        return redirect()->route('leads.index')->with('success', 'Lead deleted successfully.');
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'lead_ids' => 'required|array',
            'lead_ids.*' => 'string'
        ]);

        $query = Lead::visibleTo(auth()->user())->whereIn('id', $request->lead_ids);
        $deletedCount = $query->delete();

        return redirect()->back()->with('success', "{$deletedCount} lead(s) deleted successfully.");
    }

    public function debugDbCheck()
    {
        if (!app()->environment('local')) {
            abort(404);
        }

        $userId = auth()->id();
        
        $totalLeads = Lead::count();
        $nullUserLeads = Lead::whereNull('user_id')->count();
        $ownedLeads = Lead::where('user_id', $userId)->count();
        
        $latestLeads = Lead::orderBy('created_at', 'desc')
            ->take(10)
            ->get(['id', 'user_id', 'person_name', 'personal_email_address', 'company_name', 'source', 'created_at']);

        return response()->json([
            'auth_user_id' => $userId,
            'counts' => [
                'total_leads' => $totalLeads,
                'null_user_leads' => $nullUserLeads,
                'owned_leads' => $ownedLeads,
            ],
            'latest_10_leads' => $latestLeads
        ]);
    }
    public function debugVisibility()
    {
        if (!app()->environment('local')) {
            abort(404);
        }

        $user = auth()->user();
        
        $totalLeads = Lead::count();
        $visibleLeads = Lead::visibleTo($user)->count();
        $ownedLeads = Lead::where('user_id', $user->id)->count();
        $nullUserLeads = Lead::whereNull('user_id')->count();
        
        $latestLeads = Lead::orderBy('created_at', 'desc')
            ->take(10)
            ->get(['id', 'user_id', 'person_name', 'company_name', 'source', 'created_at']);

        return response()->json([
            'current_user' => [
                'id' => $user->id,
                'role' => $user->role,
                'name' => $user->name,
            ],
            'visibility_stats' => [
                'total_leads_in_db' => $totalLeads,
                'leads_visible_to_you' => $visibleLeads,
                'leads_strictly_owned_by_you' => $ownedLeads,
                'unassigned_leads_in_db' => $nullUserLeads,
            ],
            'latest_10_leads' => $latestLeads
        ]);
    }

    public function export(Request $request)
    {
        $query = Lead::visibleTo(auth()->user());

        // Apply same filters as index
        if ($q = $request->input('q')) {
            $query->where(function ($query) use ($q) {
                $query->where('person_name', 'ilike', "%{$q}%")
                      ->orWhere('personal_email_address', 'ilike', "%{$q}%")
                      ->orWhere('company_name', 'ilike', "%{$q}%");
            });
        }

        $leads = $query->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="leads_export_'.now()->format('Y-m-d_His').'.csv"',
        ];

        $callback = function() use ($leads) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Name', 'Email', 'Position', 'Company', 'Industry', 'City', 'Country', 'Source', 'LinkedIn', 'Created At']);

            foreach ($leads as $lead) {
                fputcsv($file, [
                    $lead->person_name,
                    $lead->personal_email_address,
                    $lead->position_by_search_param ?: $lead->position_by_apifiapi,
                    $lead->company_name,
                    $lead->industry_by_search_param,
                    $lead->city_by_search_param,
                    $lead->country_by_search_param,
                    $lead->source,
                    $lead->personal__linkdin_url,
                    $lead->created_at,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Return a single lead as JSON (Legacy - redirects to LeadSearch scoped version).
     */
    public function showJson($id)
    {
        $lead = Lead::findOrFail($id);
        
        if ($lead->lead_search_id) {
            return redirect()->route('lead-searches.leads.json', [$lead->lead_search_id, $lead->id]);
        }

        abort(404, 'Lead not associated with a search query.');
    }

}
