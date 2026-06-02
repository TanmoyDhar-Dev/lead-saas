<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LeadAutomationDetail;

class CampaignLogController extends Controller
{
    public function index()
    {
        $logs = LeadAutomationDetail::with('lead')
            ->where('email_sent', '!=', 'no operation yet')
            ->orderBy('updated_at', 'desc')
            ->paginate(20);

        return view('emails.index', compact('logs'));
    }
}
