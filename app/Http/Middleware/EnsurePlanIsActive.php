<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class EnsurePlanIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            // Check if user is not admin, has a plan, and plan is expired
            if ($user->role !== 'admin' && $user->userPlan && $user->userPlan->expiry_date && now()->greaterThan($user->userPlan->expiry_date)) {
                // If the user is trying to access the suspended page or logout, let them pass
                if ($request->routeIs('account.suspended') || $request->routeIs('logout')) {
                    return $next($request);
                }

                // Otherwise, abort and redirect to suspended page
                return redirect()->route('account.suspended');
            }
        }

        return $next($request);
    }
}
