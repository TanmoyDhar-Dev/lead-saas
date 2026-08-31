<?php

namespace App\Http\Controllers;

use App\Models\EmailTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TemplateController extends Controller
{
    public function index()
    {
        if (auth()->user()->isAdmin()) {
            $templates = EmailTemplate::orderByDesc('is_system_sample')->orderBy('created_at', 'desc')->get();
        } else {
            $templates = EmailTemplate::where('user_id', auth()->id())
                ->orWhereRaw('is_system_sample = true')
                ->orderByDesc('is_system_sample')
                ->orderBy('created_at', 'desc')
                ->get();
        }
        return view('templates.index', compact('templates'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                \Illuminate\Validation\Rule::unique('email_templates', 'name')->where('user_id', auth()->id()),
            ],
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'signature_name' => 'nullable|string|max:255',
            'signature_position' => 'nullable|string|max:255',
            'signature_company' => 'nullable|string|max:255',
            'signature_address' => 'nullable|string|max:255',
        ]);

        $validated['user_id'] = auth()->id();

        EmailTemplate::create($validated);

        return redirect()->route('templates.index')->with('success', 'Template saved successfully');
    }

    /**
     * Save the current outreach modal configuration as a reusable email template (AJAX).
     */
    public function storeFromOutreach(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('email_templates', 'name')->where('user_id', auth()->id()),
            ],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'signature_name' => ['nullable', 'string', 'max:255'],
            'signature_position' => ['nullable', 'string', 'max:255'],
            'signature_company' => ['nullable', 'string', 'max:255'],
            'signature_address' => ['nullable', 'string', 'max:255'],
        ]);

        $template = EmailTemplate::create([
            'user_id' => auth()->id(),
            'name' => $validated['name'],
            'subject' => $validated['subject'],
            'body' => $validated['body'],
            'signature_name' => $validated['signature_name'] ?? null,
            'signature_position' => $validated['signature_position'] ?? null,
            'signature_company' => $validated['signature_company'] ?? null,
            'signature_address' => $validated['signature_address'] ?? null,
        ]);

        return response()->json([
            'message' => 'Template saved successfully.',
            'template' => $this->templatePayload($template),
        ], 201);
    }

    public function create()
    {
        return view('templates.create');
    }

    public function edit($id)
    {
        $template = EmailTemplate::findOrFail($id);
        
        if (!auth()->user()->isAdmin() && $template->user_id !== auth()->id()) {
            abort(403);
        }

        if ($template->is_system_sample && !auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized to edit system sample templates.');
        }

        return view('templates.edit', compact('template'));
    }

    public function update(Request $request, $id)
    {
        $template = EmailTemplate::findOrFail($id);

        if (!auth()->user()->isAdmin() && $template->user_id !== auth()->id()) {
            abort(403);
        }

        if ($template->is_system_sample && !auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized to edit system sample templates.');
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                \Illuminate\Validation\Rule::unique('email_templates', 'name')
                    ->where('user_id', auth()->id())
                    ->ignore($template->id),
            ],
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'signature_name' => 'nullable|string|max:255',
            'signature_position' => 'nullable|string|max:255',
            'signature_company' => 'nullable|string|max:255',
            'signature_address' => 'nullable|string|max:255',
        ]);

        $template->update($validated);

        return redirect()->route('templates.index')->with('success', 'Template updated successfully.');
    }

    public function setDefault($id)
    {
        $template = EmailTemplate::findOrFail($id);
        if (! auth()->user()->isAdmin() && $template->user_id !== auth()->id()) {
            abort(403);
        }

        // Toggle off — use query builder + Postgres boolean literals.
        // Eloquent boolean cast turns the string 'false' into true ((bool) 'false' === true).
        if ($template->is_default) {
            EmailTemplate::query()
                ->whereKey($template->id)
                ->update(['is_default' => DB::raw('false')]);

            return back()->with('success', 'Default status removed.');
        }

        // Clear other defaults for this owner, then activate this template
        EmailTemplate::query()
            ->where('user_id', $template->user_id)
            ->update(['is_default' => DB::raw('false')]);

        EmailTemplate::query()
            ->whereKey($template->id)
            ->update(['is_default' => DB::raw('true')]);

        return back()->with('success', 'Default template updated.');
    }

    public function destroy($id)
    {
        $template = EmailTemplate::findOrFail($id);
        if (!auth()->user()->isAdmin() && $template->user_id !== auth()->id()) {
            abort(403);
        }

        if ($template->is_system_sample && !auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized to delete system sample templates.');
        }

        $template->delete();

        return back()->with('success', 'Template deleted successfully.');
    }

    public function bulkDelete(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $user = $request->user();
        $query = EmailTemplate::query()->whereIn('id', $validated['ids']);

        if (! $user->isAdmin()) {
            $query->where('user_id', $user->id)->whereRaw('is_system_sample = false');
        }

        $deleted = $query->delete();

        return back()->with('success', "{$deleted} template(s) deleted.");
    }

    /**
     * @return array<string, mixed>
     */
    private function templatePayload(EmailTemplate $template): array
    {
        return [
            'id' => $template->id,
            'name' => $template->name,
            'subject' => $template->subject,
            'body' => $template->body,
            'signature_name' => $template->signature_name,
            'signature_position' => $template->signature_position,
            'signature_company' => $template->signature_company,
            'signature_address' => $template->signature_address,
            'is_default' => (bool) $template->is_default,
            'is_system_sample' => (bool) $template->is_system_sample,
        ];
    }
}
