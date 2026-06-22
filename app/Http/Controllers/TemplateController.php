<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EmailTemplate;

class TemplateController extends Controller
{
    public function index()
    {
        if (auth()->user()->isAdmin()) {
            $templates = EmailTemplate::orderByDesc('is_system_sample')->orderBy('created_at', 'desc')->get();
        } else {
            $templates = EmailTemplate::where('user_id', auth()->id())
                ->orWhere('is_system_sample', 'true')
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
        $query = EmailTemplate::query();
        if (!auth()->user()->isAdmin()) {
            $query->where('user_id', auth()->id());
        }
        $query->update(['is_default' => 'false']);
        
        $template = EmailTemplate::findOrFail($id);
        if (!auth()->user()->isAdmin() && $template->user_id !== auth()->id()) {
            abort(403);
        }
        $template->update(['is_default' => 'true']);

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
}
