<?php

namespace App\Http\Controllers;

use App\Models\ImportedLead;
use App\Models\ImportedOutreachRecipient;
use App\Models\LeadCategory;
use App\Models\User;
use App\Services\LeadImport\LeadImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class ImportedLeadController extends Controller
{
    /**
     * Allowed demo import templates (fixed basenames — no user-controlled paths).
     *
     * @var array<string, array{file: string, mime: string}>
     */
    private const IMPORT_TEMPLATES = [
        'csv' => [
            'file' => 'import_leads_template.csv',
            'mime' => 'text/csv; charset=UTF-8',
        ],
        'xlsx' => [
            'file' => 'import_leads_template.xlsx',
            'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ],
    ];

    public function downloadImportTemplate(Request $request): BinaryFileResponse
    {
        $format = strtolower((string) $request->query('format', 'csv'));

        if (! array_key_exists($format, self::IMPORT_TEMPLATES)) {
            abort(404, 'Import template is not available.');
        }

        $basename = self::IMPORT_TEMPLATES[$format]['file'];
        $mime = self::IMPORT_TEMPLATES[$format]['mime'];
        $directory = storage_path('app/templates');
        $path = $directory.DIRECTORY_SEPARATOR.$basename;

        // Resolve + realpath to block traversal / symlink escapes outside the templates dir.
        $realDirectory = realpath($directory);
        $realPath = realpath($path);

        if ($realDirectory === false || $realPath === false || ! is_file($realPath)) {
            abort(404, 'Import template is not available.');
        }

        $prefix = $realDirectory.DIRECTORY_SEPARATOR;
        if (! str_starts_with($realPath, $prefix)) {
            abort(404, 'Import template is not available.');
        }

        return response()->download($realPath, $basename, ['Content-Type' => $mime]);
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $query = ImportedLead::visibleTo($user)
            ->with([
                'emails',
                'phones',
                'categories',
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

        $categoryId = trim((string) $request->input('category', ''));
        if ($categoryId !== '') {
            $query->whereHas('categories', function ($builder) use ($categoryId, $user) {
                $builder->where('lead_categories.id', $categoryId)
                    ->where('lead_categories.user_id', $user->id);
            });
        }

        $importedLeads = $query->paginate(20)->withQueryString();

        $leadCategories = LeadCategory::query()
            ->ownedBy($user)
            ->orderBy('name')
            ->get(['id', 'name', 'color']);

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

        return view('imported-leads.index', compact(
            'importedLeads',
            'templates',
            'outlookConnected',
            'leadCategories',
            'categoryId'
        ));
    }

    public function threads(Request $request)
    {
        $user = $request->user();

        $query = ImportedLead::visibleTo($user)
            ->whereHas('outreachRecipients')
            ->with([
                'emails',
                'outreachRecipients' => fn ($q) => $q->orderByDesc('updated_at'),
            ])
            ->orderByDesc('updated_at');

        if ($q = trim((string) $request->input('q'))) {
            $query->where(function ($builder) use ($q) {
                $builder->where('organization_name', 'ilike', "%{$q}%")
                    ->orWhere('contact_name', 'ilike', "%{$q}%")
                    ->orWhereHas('emails', fn ($emailQuery) => $emailQuery->where('email', 'ilike', "%{$q}%"));
            });
        }

        $leads = $query->paginate(30)->withQueryString();
        $outlookConnected = $user->microsoftMailbox()->exists();
        $selectedLeadId = trim((string) $request->query('lead', ''));

        return view('imported-leads.threads', compact(
            'leads',
            'outlookConnected',
            'selectedLeadId',
        ));
    }

    public function import(Request $request, LeadImportService $importService)
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'max:10240'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['uuid'],
            'category_names' => ['nullable', 'array'],
            'category_names.*' => ['string', 'max:100'],
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

        $user = $request->user();
        $categoryIds = $this->resolveImportCategoryIds(
            $user,
            $validated['category_ids'] ?? [],
            $validated['category_names'] ?? [],
        );

        try {
            $result = $importService->import($user, $file, $categoryIds);
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

        $leadCategories = LeadCategory::query()
            ->ownedBy($user)
            ->orderBy('name')
            ->get(['id', 'name', 'color']);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'created' => $result['created'],
                'skipped' => $result['skipped'],
                'errors' => $result['errors'],
                'error_samples' => $result['error_samples'],
                'categories' => $leadCategories,
            ]);
        }

        return redirect()
            ->route('imported-leads.index')
            ->with('success', $message);
    }

    /**
     * Resolve owned category IDs and create any new named tags for this import.
     *
     * @param  list<string>  $categoryIds
     * @param  list<string>  $categoryNames
     * @return list<string>
     */
    private function resolveImportCategoryIds(User $user, array $categoryIds, array $categoryNames): array
    {
        $resolved = [];

        if ($categoryIds !== []) {
            $owned = LeadCategory::query()
                ->ownedBy($user)
                ->whereIn('id', $categoryIds)
                ->pluck('id')
                ->all();

            $resolved = array_merge($resolved, $owned);
        }

        foreach ($categoryNames as $rawName) {
            $name = trim((string) $rawName);
            if ($name === '') {
                continue;
            }

            $category = LeadCategory::query()->firstOrCreate(
                [
                    'user_id' => $user->id,
                    'name' => $name,
                ],
                [
                    'color' => null,
                ]
            );

            $resolved[] = $category->id;
        }

        return array_values(array_unique($resolved));
    }

    public function show(Request $request, ImportedLead $importedLead)
    {
        abort_unless($importedLead->isOwnedBy($request->user()), 403);

        $importedLead->load([
            'emails',
            'phones',
            'importBatch',
            'outreachRecipients' => fn ($q) => $q->orderBy('created_at')->orderBy('id'),
            'outreachRecipients.inboundMessages' => fn ($q) => $q->orderBy('received_at')->orderBy('created_at'),
        ]);

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
            'email_thread' => $this->buildEmailThread($importedLead),
            'can_reply' => $importedLead->outreachRecipients
                ->contains(fn ($r) => $r->status === 'sent'),
        ]);
    }

    /**
     * Chronological outbound + inbound messages for the detail modal.
     * Order: outreach → lead replies → your replies → later lead replies.
     *
     * @return list<array<string, mixed>>
     */
    private function buildEmailThread(ImportedLead $importedLead): array
    {
        $thread = collect();

        $recipients = $importedLead->outreachRecipients
            ->sort(function (ImportedOutreachRecipient $a, ImportedOutreachRecipient $b): int {
                return [$this->threadSortTimestamp($a->created_at), (string) $a->id]
                    <=> [$this->threadSortTimestamp($b->created_at), (string) $b->id];
            })
            ->values();

        $firstOutboundId = $recipients
            ->first(fn (ImportedOutreachRecipient $r) => $r->final_body
                || in_array($r->status, ['sent', 'drafted', 'failed'], true))
            ?->id;

        foreach ($recipients as $recipient) {
            $outboundAt = $recipient->sent_at ?? $recipient->drafted_at ?? $recipient->created_at;
            $isUserReply = $firstOutboundId !== null && (string) $recipient->id !== (string) $firstOutboundId;

            if ($recipient->final_body || in_array($recipient->status, ['sent', 'drafted', 'failed'], true)) {
                $thread->push([
                    'id' => 'out-'.$recipient->id,
                    'direction' => 'outbound',
                    'label' => $isUserReply ? 'Sent by You' : 'Sent by System',
                    'from_email' => null,
                    'to_email' => $recipient->to_email,
                    'subject' => $recipient->subject,
                    'status' => $recipient->status,
                    'body_html' => $this->sanitizeEmailHtml($recipient->final_body),
                    'body_text' => $this->plainTextFromHtml($recipient->final_body),
                    'occurred_at' => optional($outboundAt)?->toIso8601String(),
                    'occurred_at_label' => optional($outboundAt)?->format('M d, Y h:i A'),
                    'sort_ts' => $this->threadSortTimestamp($outboundAt),
                    'sort_seq' => $this->threadSortTimestamp($recipient->created_at),
                    // outreach (0) → lead inbound (1) → your reply (2)
                    'sort_rank' => $isUserReply ? 2 : 0,
                ]);
            }

            $inbounds = $recipient->inboundMessages
                ->sort(function ($a, $b) use ($recipient): int {
                    return [
                        $this->threadSortTimestamp($this->resolveInboundOccurredAt($a, $recipient)),
                        $this->threadSortTimestamp($a->created_at),
                        (string) $a->id,
                    ] <=> [
                        $this->threadSortTimestamp($this->resolveInboundOccurredAt($b, $recipient)),
                        $this->threadSortTimestamp($b->created_at),
                        (string) $b->id,
                    ];
                })
                ->values();

            foreach ($inbounds as $inbound) {
                $inboundAt = $this->resolveInboundOccurredAt($inbound, $recipient);
                $thread->push([
                    'id' => 'in-'.$inbound->id,
                    'direction' => 'inbound',
                    'label' => 'Received from Lead',
                    'from_email' => $inbound->from_email,
                    'to_email' => null,
                    'subject' => $inbound->subject,
                    'status' => 'received',
                    'body_html' => $this->sanitizeEmailHtml($inbound->body_html)
                        ?: $this->sanitizeEmailHtml(nl2br(e((string) ($inbound->body_text ?? '')), false)),
                    'body_text' => $inbound->body_text
                        ?: $this->plainTextFromHtml($inbound->body_html),
                    'occurred_at' => optional($inboundAt)?->toIso8601String(),
                    'occurred_at_label' => optional($inboundAt)?->format('M d, Y h:i A'),
                    'sort_ts' => $this->threadSortTimestamp($inboundAt),
                    'sort_seq' => $this->threadSortTimestamp($inbound->created_at),
                    'sort_rank' => 1,
                ]);
            }
        }

        return $thread
            ->sort(function (array $a, array $b): int {
                return [$a['sort_ts'], $a['sort_rank'], $a['sort_seq'], $a['id']]
                    <=> [$b['sort_ts'], $b['sort_rank'], $b['sort_seq'], $b['id']];
            })
            ->map(function (array $row) {
                unset($row['sort_ts'], $row['sort_seq'], $row['sort_rank']);

                return $row;
            })
            ->values()
            ->all();
    }

    /**
     * Prefer Graph received_at, but never place a reply before its parent outreach
     * (common when UTC receivedDateTime was stored without timezone conversion).
     */
    private function resolveInboundOccurredAt(object $inbound, ImportedOutreachRecipient $recipient): mixed
    {
        $receivedAt = $inbound->received_at ?? null;
        $createdAt = $inbound->created_at ?? null;
        $parentAt = $recipient->sent_at ?? $recipient->drafted_at ?? $recipient->created_at;

        if ($receivedAt && $parentAt && $receivedAt->lt($parentAt)) {
            return $createdAt ?? $receivedAt;
        }

        return $receivedAt ?? $createdAt;
    }

    private function threadSortTimestamp(mixed $value): float
    {
        if ($value instanceof \Carbon\CarbonInterface) {
            return (float) $value->format('U.u');
        }

        if ($value instanceof \DateTimeInterface) {
            return (float) \Carbon\Carbon::instance($value)->format('U.u');
        }

        return 0.0;
    }

    private function sanitizeEmailHtml(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return null;
        }

        $allowed = '<p><br><br/><b><strong><i><em><u><a><ul><ol><li><div><span><table><thead><tbody><tr><td><th><h1><h2><h3><h4><blockquote><hr>';
        $clean = strip_tags($html, $allowed);
        $clean = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $clean) ?? $clean;
        $clean = preg_replace('/(href|src)\s*=\s*("|\')\s*javascript:[^"\']*\2/i', '$1="#"', $clean) ?? $clean;

        return $clean;
    }

    private function plainTextFromHtml(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return null;
        }

        $text = trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return $text !== '' ? $text : null;
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

    public function bulkDelete(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['uuid'],
        ]);

        $user = $request->user();

        $deleted = ImportedLead::visibleTo($user)
            ->whereIn('id', $validated['ids'])
            ->delete();

        return back()->with('success', "{$deleted} imported lead(s) deleted.");
    }
}
