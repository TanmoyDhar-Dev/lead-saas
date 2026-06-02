<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EmailTemplate;

class TemplateController extends Controller
{
    public function index()
    {
        $templates = EmailTemplate::orderBy('created_at', 'desc')->get();
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

        EmailTemplate::create($validated);

        return back()->with('success', 'Template saved successfully.');
    }

    public function setDefault($id)
    {
        EmailTemplate::query()->update(['is_default' => false]);
        EmailTemplate::findOrFail($id)->update(['is_default' => true]);

        return back()->with('success', 'Default template updated.');
    }

    public function destroy($id)
    {
        EmailTemplate::destroy($id);

        return back()->with('success', 'Template deleted successfully.');
    }
}
