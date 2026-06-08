<?php

namespace App\Http\Controllers;

use App\Models\CampaignRecipient;
use Illuminate\Http\Request;

class OpenedEmailController extends Controller
{
    public function index(Request $request)
    {
        $openedEmails = CampaignRecipient::visibleTo($request->user())
            ->with(['lead', 'campaign'])
            ->where('status', 'sent')
            ->whereNotNull('opened_at')
            ->orderByDesc('opened_at')
            ->paginate(20);

        return view('opened-emails.index', compact('openedEmails'));
    }
}
