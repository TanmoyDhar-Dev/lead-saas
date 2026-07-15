<?php

namespace App\Http\Controllers;

use App\Models\ImportedLead;
use App\Services\LeadImport\LeadImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class ImportedLeadController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = ImportedLead::visibleTo($user)
            ->with([
                'emails',
                'phones',
                'outreachRecipients' => fn ($q) => $q
                    ->whereIn('status', ['sent', 'drafted', 'failed', 'pending'])
                    ->orderByDesc('updated_at'),
            ])
            ->orderByDesc('created_at');

        if ($q = trim((string) $request->input('q'))) {
            $query->where(function ($builder) use ($q) {
                $builder->where('organization_name', 'ilike', "%{$q}%")
                    ->orWhere('contact_name', 'ilike', "%{$q}%")
                    ->orWhere('address', 'ilike', "%{$q}%")
                    ->orWhereHas('emails', fn ($emailQuery) => $emailQuery->where('email', 'ilike', "%{$q}%"))
                    ->orWhereHas('phones', fn ($phoneQuery) => $phoneQuery->where('phone', 'ilike', "%{$q}%"));
            });
        }

        $importedLeads = $query->paginate(20)->withQueryString();

        $templateQuery = \App\Models\EmailTemplate::query();
        if (! $user->isAdmin()) {
            $templateQuery->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)->orWhere('is_system_sample', 'true');
            });
        }
        $templates = $templateQuery->orderByDesc('is_default')->get();

        $outlookConnected = $user->microsoftMailbox()->exists();

        if ($request->ajax()) {
            return view('imported-leads.partials.table', compact('importedLeads'))->render();
        }

        return view('imported-leads.index', compact('importedLeads', 'templates', 'outlookConnected'));
    }

    public function import(Request $request, LeadImportService $importService)
    {
        $request->validate([
            'file' => ['required', 'file', 'max:10240'],
        ], [
            'file.required' => 'Please choose a CSV or Excel file.',
            'file.max' => 'File size must be 10 MB or less.',
        ]);

        $file = $request->file('file');
        $extension = strtolower((string) $file->getClientOriginalExtension());
        if (! in_array($extension, ['csv', 'xlsx', 'xls'], true)) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only CSV, XLSX, and XLS files are allowed.',
                ], 422);
            }

            return back()->withErrors(['file' => 'Only CSV, XLSX, and XLS files are allowed.']);
        }

        try {
            $result = $importService->import($request->user(), $file);
        } catch (Throwable $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->withErrors(['file' => $e->getMessage()]);
        }

        $message = "Import complete: {$result['created']} created";
        if ($result['skipped'] > 0) {
            $message .= ", {$result['skipped']} skipped";
        }
        if ($result['errors'] > 0) {
            $message .= ", {$result['errors']} failed";
        }
        $message .= '.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'created' => $result['created'],
                'skipped' => $result['skipped'],
                'errors' => $result['errors'],
                'error_samples' => $result['error_samples'],
            ]);
        }

        return redirect()
            ->route('imported-leads.index')
            ->with('success', $message);
    }

    public function show(Request $request, ImportedLead $importedLead)
    {
        abort_unless($importedLead->isOwnedBy($request->user()), 403);

        $importedLead->load(['emails', 'phones', 'importBatch']);

        return response()->json([
            'id' => $importedLead->id,
            'organization_name' => $importedLead->organization_name,
            'contact_name' => $importedLead->contact_name,
            'address' => $importedLead->address,
            'original_filename' => $importedLead->original_filename,
            'created_at' => optional($importedLead->created_at)->format('M d, Y H:i'),
            'updated_at' => optional($importedLead->updated_at)->format('M d, Y H:i'),
            'emails' => $importedLead->emails->map(fn ($email) => [
                'id' => $email->id,
                'email' => $email->email,
                'is_primary' => (bool) $email->is_primary,
            ])->values(),
            'phones' => $importedLead->phones->map(fn ($phone) => [
                'id' => $phone->id,
                'phone' => $phone->phone,
                'is_primary' => (bool) $phone->is_primary,
            ])->values(),
        ]);
    }

    public function update(Request $request, ImportedLead $importedLead)
    {
        abort_unless($importedLead->isOwnedBy($request->user()), 403);

        $validated = $request->validate([
            'organization_name' => ['nullable', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:2000'],
            'emails' => ['required', 'array', 'min:1'],
            'emails.*' => ['required', 'email', 'max:255'],
            'phones' => ['nullable', 'array'],
            'phones.*' => ['nullable', 'string', 'max:100'],
        ]);

        $emails = collect($validated['emails'])
            ->map(fn ($email) => strtolower(trim($email)))
            ->filter()
            ->unique()
            ->values();

        if ($emails->isEmpty()) {
            return back()->withErrors(['emails' => 'At least one valid email is required.']);
        }

        if (blank($validated['organization_name']) && blank($validated['contact_name'])) {
            return back()->withErrors(['organization_name' => 'Organization or contact name is required.']);
        }

        $phones = collect($validated['phones'] ?? [])
            ->map(fn ($phone) => trim((string) $phone))
            ->filter()
            ->unique()
            ->values();

        DB::transaction(function () use ($importedLead, $validated, $emails, $phones) {
            $importedLead->update([
                'organization_name' => $validated['organization_name'] ?: null,
                'contact_name' => $validated['contact_name'] ?: null,
                'address' => $validated['address'] ?: null,
            ]);

            $importedLead->emails()->delete();
            $importedLead->phones()->delete();

            foreach ($emails as $index => $email) {
                DB::table('imported_lead_emails')->insert([
                    'imported_lead_id' => $importedLead->id,
                    'email' => $email,
                    'is_primary' => DB::raw($index === 0 ? 'true' : 'false'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            foreach ($phones as $index => $phone) {
                DB::table('imported_lead_phones')->insert([
                    'imported_lead_id' => $importedLead->id,
                    'phone' => $phone,
                    'is_primary' => DB::raw($index === 0 ? 'true' : 'false'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Imported lead updated successfully.',
            ]);
        }

        return redirect()
            ->route('imported-leads.index')
            ->with('success', 'Imported lead updated successfully.');
    }

    public function destroy(Request $request, ImportedLead $importedLead)
    {
        abort_unless($importedLead->isOwnedBy($request->user()), 403);

        $importedLead->delete();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Imported lead deleted.',
            ]);
        }

        return redirect()
            ->route('imported-leads.index')
            ->with('success', 'Imported lead deleted.');
    }
}
