<?php

namespace App\Http\Controllers;

use App\Models\ImportedLead;
use App\Models\SenderIdentity;
use App\Services\ImportedLeadOutreachService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            'sender_name' => ['nullable', 'string', 'max:255'],
            'sender_role' => ['nullable', 'string', 'max:255'],
            'sender_company' => ['nullable', 'string', 'max:255'],
            'sender_address' => ['nullable', 'string', 'max:255'],
            'attachments.*' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:5120'],
        ]);

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
