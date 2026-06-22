<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = auth()->user(); 

        if (!$user->isActive() && !$user->isAdmin()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            
            return redirect()->route('login')->with('account_error', 'Your account has been deactivated. Please contact your administrator.');
        }

        // IMPORTANT: 'security_status' is on the related 'userPlan' table
        $rawStatus = $user->userPlan->security_status ?? ''; 

        // Normalize to lowercase to prevent case-mismatch bugs
        $status = strtolower(trim($rawStatus));

        $blockedStatuses = [
            \App\Models\UserPlan::SECURITY_INACTIVE_REVOKED,
            \App\Models\UserPlan::SECURITY_PAST_DUE,
        ];

        if (in_array($status, $blockedStatuses) && !$user->isAdmin()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            
            return redirect()->route('login')->with('account_error', 'System Access Revoked. Please contact your administrator.');
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
