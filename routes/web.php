<?php

use App\Http\Controllers\Auth\MicrosoftOAuthController;
use App\Http\Controllers\IntegrationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\LeadSearchController;
use App\Http\Controllers\OpenedEmailController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\Admin\AdminUserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/t/o/{tracking_id}.gif', [TrackingController::class, 'open'])->name('tracking.open');
Route::get('/t/c/{tracking_id}', [TrackingController::class, 'click'])->name('tracking.click');

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth', 'verified', 'active_user'])->group(function () {
    if (app()->environment('local')) {
        Route::get('/debug/leads-db-check', [LeadController::class, 'debugDbCheck'])->name('debug.leads-db-check');
        Route::get('/debug/lead-visibility', [LeadController::class, 'debugVisibility'])->name('debug.lead-visibility');
    }

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Global leads redirect for backward compat
    Route::get('/leads', function () {
        return redirect()->route('lead-searches.index');
    })->name('leads.index');

    Route::get('/leads/export', [LeadController::class, 'export'])->name('leads.export');
    Route::post('/leads/bulk-delete', [LeadController::class, 'bulkDelete'])->name('leads.bulk-delete');
    Route::get('/leads/{lead}/json', [LeadController::class, 'showJson'])->name('leads.show-json');
    Route::get('/leads/{lead}', [LeadController::class, 'show'])->name('leads.show');
    Route::delete('/leads/{lead}', [LeadController::class, 'destroy'])->name('leads.destroy');
    
    Route::get('/lead-searches', [LeadSearchController::class, 'index'])->name('lead-searches.index');
    Route::get('/lead-searches/create', [LeadSearchController::class, 'create'])->name('lead-searches.create');
    Route::post('/lead-searches', [LeadSearchController::class, 'store'])->name('lead-searches.store');
    Route::post('/lead-searches/dispatch', [LeadSearchController::class, 'dispatchOutreach'])->name('lead-searches.dispatch');
    Route::get('/lead-searches/{leadSearch}/leads', [LeadSearchController::class, 'leads'])->name('lead-searches.leads');
    Route::get('/lead-searches/{leadSearch}/leads/{lead}/json', [LeadSearchController::class, 'leadJson'])->name('lead-searches.leads.json');
    Route::delete('/lead-searches/{leadSearch}', [LeadSearchController::class, 'destroy'])->name('lead-searches.destroy');

    Route::get('/templates', [\App\Http\Controllers\TemplateController::class, 'index'])->name('templates.index');
    Route::post('/templates', [\App\Http\Controllers\TemplateController::class, 'store'])->name('templates.store');
    Route::post('/templates/{id}/default', [\App\Http\Controllers\TemplateController::class, 'setDefault'])->name('templates.default');
    Route::delete('/templates/{id}', [\App\Http\Controllers\TemplateController::class, 'destroy'])->name('templates.destroy');

    Route::get('/opened-emails', [OpenedEmailController::class, 'index'])->name('opened-emails.index');
    Route::post('/leads/dispatch', [LeadSearchController::class, 'dispatchOutreach'])->name('leads.dispatch');

});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/auth/microsoft/redirect', [MicrosoftOAuthController::class, 'redirect'])->name('auth.microsoft.redirect');
    Route::get('/auth/microsoft/callback', [MicrosoftOAuthController::class, 'callback'])->name('auth.microsoft.callback');

    Route::get('/integrations/status', [IntegrationController::class, 'status'])->name('integrations.status');
    Route::delete('/integrations/{provider}', [IntegrationController::class, 'disconnect'])->name('integrations.disconnect');
});

Route::middleware(['auth', 'active_user', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [AdminUserController::class, 'create'])->name('users.create');
    Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}/edit', [AdminUserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
    Route::post('/users/{user}/toggle-status', [AdminUserController::class, 'toggleStatus'])->name('users.toggle-status');
});

require __DIR__.'/auth.php';
