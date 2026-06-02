<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EmailTemplate;

class TemplateController extends Controller
{
    public function index()
    {
        $query = EmailTemplate::orderBy('created_at', 'desc');
        if (!auth()->user()->isAdmin()) {
            $query->where('user_id', auth()->id());
        }
        $templates = $query->get();
        return view('templates.index', compact('templates'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:email_templates,name',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'signature_name' => 'nullable|string|max:255',
            'signature_position' => 'nullable|string|max:255',
            'signature_company' => 'nullable|string|max:255',
            'signature_address' => 'nullable|string|max:255',
        ]);

        $validated['user_id'] = auth()->id();

        EmailTemplate::create($validated);

        return back()->with('success', 'Template saved successfully.');
    }

    public function setDefault($id)
    {
        $query = EmailTemplate::query();
        if (!auth()->user()->isAdmin()) {
            $query->where('user_id', auth()->id());
        }
        $query->update(['is_default' => false]);
        
        $template = EmailTemplate::findOrFail($id);
        if (!auth()->user()->isAdmin() && $template->user_id !== auth()->id()) {
            abort(403);
        }
        $template->update(['is_default' => true]);

        return back()->with('success', 'Default template updated.');
    }

    public function destroy($id)
    {
        $template = EmailTemplate::findOrFail($id);
        if (!auth()->user()->isAdmin() && $template->user_id !== auth()->id()) {
            abort(403);
        }
        $template->delete();

        return back()->with('success', 'Template deleted successfully.');
    }
}
