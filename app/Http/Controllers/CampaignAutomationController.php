<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Lead;
use App\Models\EmailBodyTemplate;
use App\Models\EmailSignatureTemplate;
use App\Models\SenderIdentity;
use App\Services\N8nEmailProcessService;
use Illuminate\Http\Request;

class CampaignAutomationController extends Controller
{
    /**
     * Local authorization check to enforce security and access control.
     */
    private function authorizeCampaignAccess(Campaign $campaign): void
    {
        $user = auth()->user();

        if (!$user) {
            abort(403, 'User is not authenticated.');
        }

        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return;
        }

        if ((int) $campaign->user_id !== (int) $user->id) {
            abort(403, 'Unauthorized access to this campaign.');
        }
    }

    public function confirm(Request $request, Campaign $campaign)
    {
        $this->authorizeCampaignAccess($campaign);

        if ($campaign->sent_to_n8n_at) {
            return redirect()->route('campaigns.show', $campaign)
                ->with('info', 'This campaign has already been sent to automation.');
        }

        $campaign->load(['campaignRecipients.lead', 'senderIdentity', 'user']);

        $ownerId = (int) $campaign->user_id;

        $templates = EmailBodyTemplate::query()
            ->where('user_id', $ownerId)
            ->orderBy('name')
            ->get();

        $signatures = EmailSignatureTemplate::query()
            ->where('user_id', $ownerId)
            ->orderBy('name')
            ->get();

        $senders = SenderIdentity::query()
            ->where('user_id', $ownerId)
            ->orderBy('name')
            ->get();

        $defaultBody = $templates->firstWhere('is_default', true);
        $defaultSig = $signatures->firstWhere('is_default', true);
        $defaultSender = $senders->firstWhere('is_default', true);

        $updated = false;
        $campaignData = [];

        if (empty($campaign->email_main_body) && $defaultBody) {
            $campaignData['email_main_body'] = $defaultBody->content;
            $campaign->email_main_body = $defaultBody->content;
            $updated = true;
        }

        if (empty($campaign->email_signature) && $defaultSig) {
            $campaignData['email_signature'] = $defaultSig->content;
            $campaign->email_signature = $defaultSig->content;
            $updated = true;
        }

        if (empty($campaign->sender_identity_id) && $defaultSender) {
            $campaignData['sender_identity_id'] = $defaultSender->id;
            $campaign->sender_identity_id = $defaultSender->id;
            $updated = true;
        }

        if ($updated) {
            $campaign->update($campaignData);
        }

        $bodyTemplatesForJs = $templates->map(fn ($template) => [
            'id' => (string) $template->id,
            'name' => $template->name,
            'content' => $template->content,
        ])->values();

        $signatureTemplatesForJs = $signatures->map(fn ($template) => [
            'id' => (string) $template->id,
            'name' => $template->name,
            'content' => $template->content,
        ])->values();

        $senderIdentitiesForJs = $senders->map(fn ($sender) => [
            'id' => (string) $sender->id,
            'name' => $sender->name,
            'sender_name' => $sender->sender_name,
            'sender_role' => $sender->sender_role,
            'sender_company' => $sender->sender_company,
            'sender_region' => $sender->sender_region,
            'sender_industry' => $sender->sender_industry,
            'sender_linkedin' => $sender->sender_linkedin,
            'sender_website' => $sender->sender_website,
            'sender_eo_chapter' => $sender->sender_eo_chapter,
        ])->values();

        return view('campaigns.confirm', compact(
            'campaign',
            'templates',
            'signatures',
            'senders',
            'bodyTemplatesForJs',
            'signatureTemplatesForJs',
            'senderIdentitiesForJs'
        ));
    }

    public function process(Request $request, Campaign $campaign)
    {
        $this->authorizeCampaignAccess($campaign);

        if ($campaign->sent_to_n8n_at) {
            return redirect()->route('campaigns.show', $campaign)
                ->with('info', 'Automation was already initiated for this campaign.');
        }

        $validated = $request->validate([
            'delivery_mode' => 'required|in:draft,send',
            'search_window' => 'required|string|in:qdr:m3,qdr:m6,qdr:y',
            'sender_identity_id' => 'required|exists:sender_identities,id',
            'email_main_body' => 'required|string',
            'email_signature' => 'nullable|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'nullable|file|max:5120|mimes:pdf,csv,txt,doc,docx,xls,xlsx,zip,png,jpg,jpeg',
        ]);

        $sender = SenderIdentity::query()->findOrFail($validated['sender_identity_id']);

        if ((int) $sender->user_id !== (int) $campaign->user_id) {
            abort(403, 'Sender identity must belong to the campaign owner.');
        }

        $campaign->load('campaignRecipients');

        $recipientLeadIds = $campaign->campaignRecipients->pluck('lead_id')->all();

        // Enforce that all recipient leads belong to the campaign owner
        $leadQuery = Lead::query()->whereIn('id', $recipientLeadIds);
        
        if (! auth()->user()->isAdmin()) {
            $leadQuery->where('user_id', auth()->id());
        } else {
            $leadQuery->where('user_id', $campaign->user_id);
        }

        if ($leadQuery->count() !== count($recipientLeadIds)) {
            abort(403, 'Campaign recipients include leads that are not allowed for this campaign.');
        }

        // 1. First save all inputs and set temporary status
        $campaign->update([
            'delivery_mode' => $validated['delivery_mode'],
            'search_window' => $validated['search_window'],
            'sender_identity_id' => $validated['sender_identity_id'],
            'email_main_body' => $validated['email_main_body'],
            'email_signature' => $validated['email_signature'] ?? null,
            'status' => 'processing',
            'error_message' => null,
        ]);

        $campaign->refresh()->load(['campaignRecipients.lead', 'senderIdentity']);

        // Convert uploaded files to base64 attachment array
        $attachments = collect($request->file('attachments', []))->filter()->map(function ($file) {
            return [
                'filename' => $file->getClientOriginalName(),
                'mimeType' => $file->getMimeType(),
                'size' => $file->getSize(),
                'base64' => base64_encode(file_get_contents($file->getRealPath())),
            ];
        })->values()->all();

        // 2. Dispatch to the n8n webhook service
        $n8nResult = app(N8nEmailProcessService::class)->send($campaign, $attachments);

        if ($n8nResult['successful']) {
            $campaign->update([
                'sent_to_n8n_at' => now(),
                'n8n_response' => is_array($n8nResult['body']) ? $n8nResult['body'] : ['raw' => $n8nResult['body']],
                'status' => 'processing',
                'error_message' => null,
            ]);

            $campaign->campaignRecipients()->update(['status' => 'queued']);

            // Update leads email_sent column to appropriate delivery mode
            Lead::whereIn('id', $recipientLeadIds)->update([
                'email_sent' => $validated['delivery_mode'] === 'draft' ? 'drafted' : 'sent',
            ]);

            return redirect()->route('campaigns.show', $campaign)
                ->with('success', 'Automation sequence initiated successfully.');
        }

        // 3. Handle webhook failure
        $campaign->update([
            'status' => 'failed',
            'error_message' => $n8nResult['error'] ?? 'Email automation webhook failed.',
            'n8n_response' => is_array($n8nResult['body']) ? $n8nResult['body'] : (is_string($n8nResult['body']) ? ['raw' => $n8nResult['body']] : null),
        ]);

        return redirect()->route('campaigns.confirm', $campaign)
            ->with('error', $n8nResult['error'] ?? 'Email automation webhook failed.');
    }

    public function cancel(Request $request, Campaign $campaign)
    {
        $this->authorizeCampaignAccess($campaign);

        if ($campaign->sent_to_n8n_at) {
            return redirect()->route('campaigns.show', $campaign)
                ->with('error', 'Cannot cancel a campaign that has already been sent to automation.');
        }

        $campaign->delete();

        return redirect()->route('campaigns.index')
            ->with('success', 'Session cancelled and draft deleted successfully.');
    }
}
