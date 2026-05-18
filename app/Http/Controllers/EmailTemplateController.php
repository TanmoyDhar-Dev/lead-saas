<?php

namespace App\Http\Controllers;

use App\Models\EmailBodyTemplate;
use App\Models\EmailSignatureTemplate;
use Illuminate\Http\Request;

class EmailTemplateController extends Controller
{
    public function index()
    {
        $bodies = EmailBodyTemplate::visibleTo(auth()->user())->get();
        $signatures = EmailSignatureTemplate::visibleTo(auth()->user())->get();
        
        return view('settings.templates', compact('bodies', 'signatures'));
    }
}
