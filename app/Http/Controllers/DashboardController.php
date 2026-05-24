<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Lead;
use App\Models\LeadSearch;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        if ($user->isAdmin()) {
            $stats = [
                'total_users' => User::count(),
                'total_leads' => Lead::count(),
                'total_searches' => LeadSearch::count(),
            ];
            
            $latestLeads = Lead::with('user')->orderByDesc('created_at')->take(5)->get();
            $latestSearches = LeadSearch::with('user')->orderByDesc('created_at')->take(5)->get();
            
            return view('dashboard', compact('stats', 'latestLeads', 'latestSearches'));
        }

        $stats = [
            'total_leads' => Lead::where('user_id', $user->id)->count(),
            'total_searches' => LeadSearch::where('user_id', $user->id)->count(),
        ];
        
        $latestLeads = Lead::where('user_id', $user->id)->orderByDesc('created_at')->take(5)->get();
        $latestSearches = LeadSearch::where('user_id', $user->id)->orderByDesc('created_at')->take(5)->get();

        return view('dashboard', compact('stats', 'latestLeads', 'latestSearches'));
    }
}
