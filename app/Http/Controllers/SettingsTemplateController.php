<?php

namespace App\Http\Controllers;

use App\Models\EmailBodyTemplate;
use App\Models\EmailSignatureTemplate;
use Illuminate\Http\Request;

class SettingsTemplateController extends Controller
{
    /**
     * Helper to authorize user owns the record or is an admin.
     */
    private function authorizeOwnerOrAdmin($record): void
    {
        $user = auth()->user();

        if ($user->isAdmin()) {
            return;
        }

        if ((int) $record->user_id !== (int) $user->id) {
            abort(403, 'Unauthorized access to this template.');
        }
    }

    public function index()
    {
        $user = auth()->user();

        // Default: settings page shows records for the current user.
        $bodyTemplates = EmailBodyTemplate::query()
            ->when(!$user->isAdmin(), fn ($q) => $q->where('user_id', $user->id))
            ->orderBy('name')
            ->get();

        $signatureTemplates = EmailSignatureTemplate::query()
            ->when(!$user->isAdmin(), fn ($q) => $q->where('user_id', $user->id))
            ->orderBy('name')
            ->get();

        return view('settings.templates', compact('bodyTemplates', 'signatureTemplates'));
    }

    public function storeBody(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'nullable|string|max:255',
            'content' => 'required|string',
            'is_default' => 'nullable|boolean',
        ]);

        $user = auth()->user();
        $isDefault = (bool) ($validated['is_default'] ?? false);

        if ($isDefault) {
            EmailBodyTemplate::query()->where('user_id', $user->id)->update(['is_default' => false]);
        }

        EmailBodyTemplate::create([
            'user_id' => $user->id,
            'name' => $validated['name'],
            'subject' => $validated['subject'] ?? null,
            'content' => $validated['content'],
            'is_default' => $isDefault,
        ]);

        return redirect()->route('settings.templates')->with('success', 'Email body template created successfully.');
    }

    public function updateBody(Request $request, EmailBodyTemplate $template)
    {
        $this->authorizeOwnerOrAdmin($template);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'nullable|string|max:255',
            'content' => 'required|string',
            'is_default' => 'nullable|boolean',
        ]);

        $isDefault = (bool) ($validated['is_default'] ?? false);

        if ($isDefault) {
            EmailBodyTemplate::query()->where('user_id', $template->user_id)->update(['is_default' => false]);
        }

        $template->update([
            'name' => $validated['name'],
            'subject' => $validated['subject'] ?? null,
            'content' => $validated['content'],
            'is_default' => $isDefault,
        ]);

        return redirect()->route('settings.templates')->with('success', 'Email body template updated successfully.');
    }

    public function destroyBody(EmailBodyTemplate $template)
    {
        $this->authorizeOwnerOrAdmin($template);
        $template->delete();

        return redirect()->route('settings.templates')->with('success', 'Email body template deleted successfully.');
    }

    public function setDefaultBody(EmailBodyTemplate $template)
    {
        $this->authorizeOwnerOrAdmin($template);

        EmailBodyTemplate::query()->where('user_id', $template->user_id)->update(['is_default' => false]);
        $template->update(['is_default' => true]);

        return redirect()->route('settings.templates')->with('success', 'Email body template set as default.');
    }

    public function storeSignature(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|string',
            'is_default' => 'nullable|boolean',
        ]);

        $user = auth()->user();
        $isDefault = (bool) ($validated['is_default'] ?? false);

        if ($isDefault) {
            EmailSignatureTemplate::query()->where('user_id', $user->id)->update(['is_default' => false]);
        }

        EmailSignatureTemplate::create([
            'user_id' => $user->id,
            'name' => $validated['name'],
            'content' => $validated['content'],
            'is_default' => $isDefault,
        ]);

        return redirect()->route('settings.templates')->with('success', 'Email signature template created successfully.');
    }

    public function updateSignature(Request $request, EmailSignatureTemplate $signature)
    {
        $this->authorizeOwnerOrAdmin($signature);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|string',
            'is_default' => 'nullable|boolean',
        ]);

        $isDefault = (bool) ($validated['is_default'] ?? false);

        if ($isDefault) {
            EmailSignatureTemplate::query()->where('user_id', $signature->user_id)->update(['is_default' => false]);
        }

        $signature->update([
            'name' => $validated['name'],
            'content' => $validated['content'],
            'is_default' => $isDefault,
        ]);

        return redirect()->route('settings.templates')->with('success', 'Email signature template updated successfully.');
    }

    public function destroySignature(EmailSignatureTemplate $signature)
    {
        $this->authorizeOwnerOrAdmin($signature);
        $signature->delete();

        return redirect()->route('settings.templates')->with('success', 'Email signature template deleted successfully.');
    }

    public function setDefaultSignature(EmailSignatureTemplate $signature)
    {
        $this->authorizeOwnerOrAdmin($signature);

        EmailSignatureTemplate::query()->where('user_id', $signature->user_id)->update(['is_default' => false]);
        $signature->update(['is_default' => true]);

        return redirect()->route('settings.templates')->with('success', 'Email signature template set as default.');
    }
}
