<?php

namespace App\Http\Controllers;

use App\Models\CampaignRecipient;
use App\Models\ImportedOutreachRecipient;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class OpenedEmailController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $campaignRows = CampaignRecipient::visibleTo($user)
            ->with(['lead', 'campaign'])
            ->where('status', 'sent')
            ->whereNotNull('opened_at')
            ->get()
            ->map(function (CampaignRecipient $recipient) {
                return (object) [
                    'source' => 'campaign',
                    'recipient_name' => $recipient->lead?->full_name ?? 'Unknown',
                    'recipient_org' => $recipient->lead?->company_name ?? '—',
                    'recipient_email' => $recipient->lead?->personal_email
                        ?? $recipient->lead?->company_email
                        ?? '',
                    'subject' => $recipient->subject,
                    'status' => $recipient->status,
                    'sent_at' => $recipient->sent_at,
                    'opened_at' => $recipient->opened_at,
                    'open_count' => (int) ($recipient->open_count ?? 0),
                ];
            });

        $importedRows = ImportedOutreachRecipient::visibleTo($user)
            ->with(['importedLead', 'outreach'])
            ->whereNotNull('opened_at')
            ->get()
            ->map(function (ImportedOutreachRecipient $recipient) {
                return (object) [
                    'source' => 'imported',
                    'recipient_name' => $recipient->importedLead?->contact_name ?? 'Unknown',
                    'recipient_org' => $recipient->importedLead?->organization_name ?? '—',
                    'recipient_email' => $recipient->to_email ?? '',
                    'subject' => $recipient->subject,
                    'status' => $recipient->status,
                    'sent_at' => $recipient->sent_at ?? $recipient->drafted_at,
                    'opened_at' => $recipient->opened_at,
                    'open_count' => (int) ($recipient->open_count ?? 0),
                ];
            });

        /** @var Collection<int, object> $merged */
        $merged = $campaignRows
            ->concat($importedRows)
            ->sortByDesc(fn ($row) => optional($row->opened_at)->timestamp ?? 0)
            ->values();

        $perPage = 20;
        $page = max(1, (int) $request->input('page', 1));
        $openedEmails = new LengthAwarePaginator(
            $merged->forPage($page, $perPage)->values(),
            $merged->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view('opened-emails.index', compact('openedEmails'));
    }
}
