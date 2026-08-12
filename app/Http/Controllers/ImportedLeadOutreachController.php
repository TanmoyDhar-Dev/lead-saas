<?php

namespace App\Http\Controllers;

use App\Http\Requests\BulkReplyRequest;
use App\Jobs\ProcessBulkReplyJob;
use App\Models\ImportedLead;
use App\Models\ImportedOutreach;
use App\Models\ImportedOutreachRecipient;
use App\Models\SenderIdentity;
use App\Services\ImportedLeadOutreachService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ImportedLeadOutreachController extends Controller
{
    public function dispatch(Request $request, ImportedLeadOutreachService $outreachService)
    {
        $validated = $request->validate([
            'imported_lead_ids' => ['required', 'array', 'min:1'],
            'imported_lead_ids.*' => ['uuid', 'exists:imported_leads,id'],
            'delivery_mode' => ['required', 'string'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'cc_emails' => ['nullable', 'string', 'max:2000'],
            'sender_name' => ['nullable', 'string', 'max:255'],
            'sender_role' => ['nullable', 'string', 'max:255'],
            'sender_company' => ['nullable', 'string', 'max:255'],
            'sender_address' => ['nullable', 'string', 'max:255'],
            'attachments.*' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:5120'],
        ]);

        $customCcEmails = $this->parseCcEmailsInput($validated['cc_emails'] ?? null);
        if ($customCcEmails instanceof \Illuminate\Http\RedirectResponse) {
            return $customCcEmails;
        }

        $user = $request->user();

        $leads = ImportedLead::visibleTo($user)
            ->with('emails')
            ->whereIn('id', $validated['imported_lead_ids'])
            ->get();

        if ($leads->count() !== count($validated['imported_lead_ids'])) {
            return back()->withErrors(['imported_lead_ids' => 'One or more selected leads are not accessible.']);
        }

        $attachmentPaths = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $attachmentPaths[] = [
                    'path' => $file->store('imported-outreach-attachments', 'public'),
                    'name' => $file->getClientOriginalName(),
                    'contentType' => $file->getMimeType() ?: 'application/octet-stream',
                ];
            }
        }

        $deliveryMode = in_array($validated['delivery_mode'], ['Send Immediately', 'send'], true)
            ? 'send'
            : 'draft';

        $senderIdentity = $this->resolveSenderIdentity($user->id, $validated);
        $signature = $outreachService->buildSignatureHtml($validated, $senderIdentity);

        try {
            $result = $outreachService->dispatch($user, $leads, [
                'delivery_mode' => $deliveryMode,
                'subject' => $validated['subject'],
                'body' => $validated['body'],
                'email_signature' => $signature,
                'sender_identity_id' => $senderIdentity?->id,
                'sender_address' => $validated['sender_address'] ?? null,
                'cc_emails' => $customCcEmails,
                'attachments' => $attachmentPaths,
            ]);
        } catch (Throwable $e) {
            return back()->withErrors(['dispatch' => $e->getMessage()]);
        }

        if ($result['sent'] + $result['drafted'] === 0) {
            $error = $result['results'][0]['error']
                ?? $result['outreach']->error_message
                ?? 'Outreach failed for all selected leads.';

            return back()->withErrors(['dispatch' => $error]);
        }

        $parts = [];
        if ($result['sent'] > 0) {
            $parts[] = "{$result['sent']} sent";
        }
        if ($result['drafted'] > 0) {
            $parts[] = "{$result['drafted']} drafted";
        }
        if ($result['failed'] > 0) {
            $parts[] = "{$result['failed']} failed";
        }

        return back()->with('success', 'Outreach finished: '.implode(', ', $parts).'.');
    }

    public function reply(Request $request, ImportedLead $importedLead, ImportedLeadOutreachService $outreachService)
    {
        abort_unless($importedLead->isOwnedBy($request->user()), 403);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:50000'],
        ]);

        try {
            $result = $outreachService->reply($request->user(), $importedLead, $validated['body']);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        if (! $result['successful']) {
            return response()->json([
                'message' => $result['error'] ?? 'Failed to send reply.',
            ], 422);
        }

        return response()->json([
            'message' => 'Reply sent.',
        ]);
    }

    public function bulkReplyPage(Request $request)
    {
        return view('imported-leads.bulk-reply', [
            'outlookConnected' => $request->user()->microsoftMailbox()->exists(),
        ]);
    }

    public function bulkReply(BulkReplyRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $parent = ImportedOutreach::query()
            ->visibleTo($user)
            ->with(['recipients' => fn ($q) => $q->orderByDesc('sent_at')->orderByDesc('created_at')])
            ->find($validated['parent_outreach_id']);

        if ($parent === null) {
            return response()->json([
                'message' => 'Parent outreach campaign was not found.',
            ], 404);
        }

        $leads = ImportedLead::query()
            ->visibleTo($user)
            ->with('emails')
            ->whereIn('id', $validated['selected_lead_ids'])
            ->get()
            ->keyBy('id');

        if ($leads->count() !== count(array_unique($validated['selected_lead_ids']))) {
            return response()->json([
                'message' => 'One or more selected leads are not accessible.',
            ], 422);
        }

        $attachmentMeta = $this->storeBulkReplyAttachments($request);
        $replySubject = $this->bulkReplySubject((string) $parent->subject_template);

        try {
            $child = DB::transaction(function () use (
                $user,
                $parent,
                $validated,
                $leads,
                $attachmentMeta,
                $replySubject,
            ): ImportedOutreach {
                $child = ImportedOutreach::create([
                    'parent_outreach_id' => $parent->id,
                    'user_id' => $user->id,
                    'sender_identity_id' => $parent->sender_identity_id,
                    'name' => 'Bulk Reply '.now()->format('M d, Y H:i'),
                    'delivery_mode' => 'send',
                    'subject_template' => $replySubject,
                    'body_template' => $validated['body_template'],
                    'email_signature' => $parent->email_signature,
                    'attachments' => $attachmentMeta !== [] ? $attachmentMeta : null,
                    'status' => 'processing',
                ]);

                foreach ($validated['selected_lead_ids'] as $leadId) {
                    /** @var ImportedLead $lead */
                    $lead = $leads->get($leadId);
                    $parentRecipient = $parent->recipients
                        ->firstWhere('imported_lead_id', $leadId);

                    $toEmail = is_string($parentRecipient?->to_email) && $parentRecipient->to_email !== ''
                        ? $parentRecipient->to_email
                        : $lead->primaryEmail();

                    if (! is_string($toEmail) || $toEmail === '') {
                        throw new \RuntimeException(
                            "Lead {$leadId} has no email address for bulk reply."
                        );
                    }

                    ImportedOutreachRecipient::create([
                        'imported_outreach_id' => $child->id,
                        'imported_lead_id' => $lead->id,
                        'tracking_id' => (string) Str::uuid(),
                        'to_email' => $toEmail,
                        'cc_emails' => is_array($parentRecipient?->cc_emails)
                            ? $parentRecipient->cc_emails
                            : null,
                        'subject' => $replySubject,
                        'status' => 'pending',
                    ]);
                }

                return $child->load('recipients');
            });
        } catch (Throwable $e) {
            $this->cleanupBulkReplyAttachments($attachmentMeta);

            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        foreach ($child->recipients as $recipient) {
            ProcessBulkReplyJob::dispatch($recipient);
        }

        return response()->json([
            'message' => 'Bulk replies have been queued.',
            'outreach_id' => $child->id,
            'parent_outreach_id' => $parent->id,
            'queued' => $child->recipients->count(),
        ], 202);
    }

    public function campaigns(Request $request): JsonResponse
    {
        $campaigns = ImportedOutreach::query()
            ->visibleTo($request->user())
            ->whereNull('parent_outreach_id')
            ->whereHas('recipients', fn ($q) => $q->where('status', 'sent'))
            ->withCount(['recipients as sent_recipients_count' => fn ($q) => $q->where('status', 'sent')])
            ->orderByDesc('created_at')
            ->limit(100)
            ->get(['id', 'name', 'subject_template', 'status', 'created_at', 'sent_at']);

        return response()->json([
            'data' => $campaigns->map(fn (ImportedOutreach $campaign) => [
                'id' => $campaign->id,
                'name' => $campaign->name,
                'subject' => $campaign->subject_template,
                'status' => $campaign->status,
                'sent_recipients_count' => (int) $campaign->sent_recipients_count,
                'created_at_label' => optional($campaign->created_at)->format('M d, Y H:i'),
                'sent_at_label' => optional($campaign->sent_at ?? $campaign->created_at)->format('M d, Y H:i'),
            ])->values(),
        ]);
    }

    public function campaignRecipients(Request $request, ImportedOutreach $importedOutreach): JsonResponse
    {
        abort_unless(
            ImportedOutreach::query()
                ->visibleTo($request->user())
                ->whereKey($importedOutreach->id)
                ->exists(),
            403
        );

        if ($importedOutreach->parent_outreach_id !== null) {
            return response()->json([
                'message' => 'Select a parent outreach campaign, not a bulk-reply child.',
            ], 422);
        }

        $recipients = $importedOutreach->recipients()
            ->with(['importedLead:id,organization_name,contact_name'])
            ->where('status', 'sent')
            ->orderByDesc('sent_at')
            ->orderBy('to_email')
            ->get();

        return response()->json([
            'parent_outreach_id' => $importedOutreach->id,
            'subject' => $importedOutreach->subject_template,
            'reply_subject' => $this->bulkReplySubject((string) $importedOutreach->subject_template),
            'data' => $recipients->map(fn (ImportedOutreachRecipient $recipient) => [
                'recipient_id' => $recipient->id,
                'imported_lead_id' => $recipient->imported_lead_id,
                'organization_name' => $recipient->importedLead?->organization_name,
                'contact_name' => $recipient->importedLead?->contact_name,
                'to_email' => $recipient->to_email,
                'subject' => $recipient->subject,
                'status' => $recipient->status,
                'has_graph_message_id' => filled($recipient->graph_message_id),
                'sent_at_label' => optional($recipient->sent_at)->format('M d, Y H:i'),
            ])->values(),
        ]);
    }

    /**
     * @return list<array{path: string, name: string, contentType: string, disk: string}>
     */
    private function storeBulkReplyAttachments(BulkReplyRequest $request): array
    {
        if (! $request->hasFile('attachments')) {
            return [];
        }

        $stored = [];

        foreach ($request->file('attachments') as $file) {
            if ($file === null) {
                continue;
            }

            $path = $file->store('bulk-reply-attachments/'.now()->format('Y/m/d'), 'local');

            $stored[] = [
                'path' => $path,
                'name' => $file->getClientOriginalName(),
                'contentType' => $file->getMimeType() ?: 'application/octet-stream',
                'disk' => 'local',
            ];
        }

        return $stored;
    }

    /**
     * @param  list<array{path?: string, disk?: string}>  $attachments
     */
    private function cleanupBulkReplyAttachments(array $attachments): void
    {
        foreach ($attachments as $attachment) {
            $path = $attachment['path'] ?? null;
            $disk = $attachment['disk'] ?? 'local';

            if (is_string($path) && $path !== '') {
                Storage::disk($disk)->delete($path);
            }
        }
    }

    private function bulkReplySubject(string $parentSubject): string
    {
        $subject = trim($parentSubject);
        if ($subject === '') {
            return 'Re:';
        }

        return preg_match('/^re:\s*/i', $subject) === 1
            ? $subject
            : 'Re: '.$subject;
    }

    /**
     * Parse optional comma-separated CC input into unique valid emails.
     *
     * @return list<string>|\Illuminate\Http\RedirectResponse
     */
    private function parseCcEmailsInput(mixed $input): array|\Illuminate\Http\RedirectResponse
    {
        if ($input === null || $input === '') {
            return [];
        }

        $parts = is_array($input)
            ? $input
            : (preg_split('/\s*,\s*/', (string) $input) ?: []);

        $emails = [];
        $invalid = [];
        $seen = [];

        foreach ($parts as $part) {
            $email = trim((string) $part);
            if ($email === '') {
                continue;
            }

            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $invalid[] = $email;
                continue;
            }

            $key = strtolower($email);
            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $emails[] = $email;
        }

        if ($invalid !== []) {
            return back()->withErrors([
                'cc_emails' => 'Invalid CC address(es): '.implode(', ', $invalid),
            ]);
        }

        return $emails;
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
