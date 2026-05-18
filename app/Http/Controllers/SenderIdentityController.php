<?php

namespace App\Http\Controllers;

use App\Models\SenderIdentity;
use Illuminate\Http\Request;

class SenderIdentityController extends Controller
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
            abort(403, 'Unauthorized access to this sender identity.');
        }
    }

    public function index()
    {
        $user = auth()->user();

        $senders = SenderIdentity::query()
            ->when(!$user->isAdmin(), fn ($q) => $q->where('user_id', $user->id))
            ->orderBy('name')
            ->get();

        return view('settings.senders', compact('senders'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sender_name' => 'required|string|max:255',
            'sender_role' => 'nullable|string|max:255',
            'sender_company' => 'nullable|string|max:255',
            'sender_region' => 'nullable|string|max:255',
            'sender_industry' => 'nullable|string|max:255',
            'sender_linkedin' => 'nullable|url|max:2000',
            'sender_website' => 'nullable|url|max:2000',
            'sender_eo_chapter' => 'nullable|string|max:255',
            'is_default' => 'nullable|boolean',
            'status' => 'nullable|in:active,inactive',
        ]);

        $user = auth()->user();
        $isDefault = (bool) ($validated['is_default'] ?? false);

        if ($isDefault) {
            SenderIdentity::query()->where('user_id', $user->id)->update(['is_default' => false]);
        }

        SenderIdentity::create([
            'user_id' => $user->id,
            'name' => $validated['name'],
            'sender_name' => $validated['sender_name'],
            'sender_role' => $validated['sender_role'] ?? null,
            'sender_company' => $validated['sender_company'] ?? null,
            'sender_region' => $validated['sender_region'] ?? null,
            'sender_industry' => $validated['sender_industry'] ?? null,
            'sender_linkedin' => $validated['sender_linkedin'] ?? null,
            'sender_website' => $validated['sender_website'] ?? null,
            'sender_eo_chapter' => $validated['sender_eo_chapter'] ?? null,
            'is_default' => $isDefault,
            'status' => $validated['status'] ?? 'active',
        ]);

        return redirect()->route('settings.senders')->with('success', 'Sender identity created successfully.');
    }

    public function update(Request $request, SenderIdentity $sender)
    {
        $this->authorizeOwnerOrAdmin($sender);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sender_name' => 'required|string|max:255',
            'sender_role' => 'nullable|string|max:255',
            'sender_company' => 'nullable|string|max:255',
            'sender_region' => 'nullable|string|max:255',
            'sender_industry' => 'nullable|string|max:255',
            'sender_linkedin' => 'nullable|url|max:2000',
            'sender_website' => 'nullable|url|max:2000',
            'sender_eo_chapter' => 'nullable|string|max:255',
            'is_default' => 'nullable|boolean',
            'status' => 'nullable|in:active,inactive',
        ]);

        $isDefault = (bool) ($validated['is_default'] ?? false);

        if ($isDefault) {
            SenderIdentity::query()->where('user_id', $sender->user_id)->update(['is_default' => false]);
        }

        $sender->update([
            'name' => $validated['name'],
            'sender_name' => $validated['sender_name'],
            'sender_role' => $validated['sender_role'] ?? null,
            'sender_company' => $validated['sender_company'] ?? null,
            'sender_region' => $validated['sender_region'] ?? null,
            'sender_industry' => $validated['sender_industry'] ?? null,
            'sender_linkedin' => $validated['sender_linkedin'] ?? null,
            'sender_website' => $validated['sender_website'] ?? null,
            'sender_eo_chapter' => $validated['sender_eo_chapter'] ?? null,
            'is_default' => $isDefault,
            'status' => $validated['status'] ?? 'active',
        ]);

        return redirect()->route('settings.senders')->with('success', 'Sender identity updated successfully.');
    }

    public function destroy(SenderIdentity $sender)
    {
        $this->authorizeOwnerOrAdmin($sender);
        $sender->delete();

        return redirect()->route('settings.senders')->with('success', 'Sender identity deleted successfully.');
    }

    public function setDefault(SenderIdentity $sender)
    {
        $this->authorizeOwnerOrAdmin($sender);

        SenderIdentity::query()->where('user_id', $sender->user_id)->update(['is_default' => false]);
        $sender->update(['is_default' => true]);

        return redirect()->route('settings.senders')->with('success', 'Sender identity set as default.');
    }
}
