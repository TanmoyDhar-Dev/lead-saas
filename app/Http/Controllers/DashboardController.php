<?php

namespace App\Http\Controllers;

use App\Models\CampaignRecipient;
use App\Models\Lead;
use App\Models\LeadSearch;
use App\Models\User;
class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $emailStats = $this->emailStatsFor($user);

        if ($user->isAdmin()) {
            $stats = [
                'total_users' => User::count(),
                'total_leads' => Lead::count(),
                'total_searches' => LeadSearch::count(),
            ];

            $latestLeads = Lead::with('user')->orderByDesc('created_at')->take(5)->get();
            $latestSearches = LeadSearch::with('user')->orderByDesc('created_at')->take(5)->get();

            return view('dashboard', compact('stats', 'latestLeads', 'latestSearches', 'emailStats'));
        }

        $stats = [
            'total_leads' => Lead::visibleTo($user)->count(),
            'total_searches' => LeadSearch::where('user_id', $user->id)->count(),
        ];

        $latestLeads = Lead::visibleTo($user)->orderByDesc('created_at')->take(5)->get();
        $latestSearches = LeadSearch::where('user_id', $user->id)->orderByDesc('created_at')->take(5)->get();

        return view('dashboard', compact('stats', 'latestLeads', 'latestSearches', 'emailStats'));
    }

    /**
     * @return array{sent: int, drafted: int, opened: int}
     */
    private function emailStatsFor(User $user): array
    {
        $base = CampaignRecipient::visibleTo($user);

        return [
            'sent' => (clone $base)->where('status', 'sent')->count(),
            'drafted' => (clone $base)->where('status', 'drafted')->count(),
            'opened' => (clone $base)->where('status', 'sent')->whereNotNull('opened_at')->count(),
        ];
    }
}
