<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\LeadSearchController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\CampaignFromLeadSearchController;
use App\Http\Controllers\CampaignAutomationController;
use App\Http\Controllers\EmailTemplateController;
use App\Http\Controllers\SenderIdentityController;
use App\Http\Controllers\Admin\AdminUserController;
use Illuminate\Support\Facades\Route;

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
    Route::get('/lead-searches/{leadSearch}/leads', [LeadSearchController::class, 'leads'])->name('lead-searches.leads');
    Route::get('/lead-searches/{leadSearch}/leads/{lead}/json', [LeadSearchController::class, 'leadJson'])->name('lead-searches.leads.json');
    Route::delete('/lead-searches/{leadSearch}', [LeadSearchController::class, 'destroy'])->name('lead-searches.destroy');
    
    Route::get('/campaigns', [CampaignController::class, 'index'])->name('campaigns.index');
    Route::get('/campaigns/create', [CampaignController::class, 'create'])->name('campaigns.create');
    Route::post('/campaigns', [CampaignController::class, 'store'])->name('campaigns.store');
    Route::post('/campaigns/from-lead-search', [CampaignFromLeadSearchController::class, 'store'])->name('campaigns.from-lead-search');
    Route::get('/campaigns/{campaign}/confirm', [CampaignAutomationController::class, 'confirm'])->name('campaigns.confirm');
    Route::post('/campaigns/{campaign}/process', [CampaignAutomationController::class, 'process'])->name('campaigns.process');
    Route::post('/campaigns/{campaign}/cancel', [CampaignAutomationController::class, 'cancel'])->name('campaigns.cancel');
    Route::get('/campaigns/{campaign}', [CampaignController::class, 'show'])->name('campaigns.show');
    
    // Settings - Email Templates & Signatures CRUD
    Route::get('/settings/templates', [\App\Http\Controllers\SettingsTemplateController::class, 'index'])->name('settings.templates');
    Route::post('/settings/templates/body', [\App\Http\Controllers\SettingsTemplateController::class, 'storeBody'])->name('settings.templates.body.store');
    Route::put('/settings/templates/body/{template}', [\App\Http\Controllers\SettingsTemplateController::class, 'updateBody'])->name('settings.templates.body.update');
    Route::delete('/settings/templates/body/{template}', [\App\Http\Controllers\SettingsTemplateController::class, 'destroyBody'])->name('settings.templates.body.destroy');
    Route::post('/settings/templates/body/{template}/default', [\App\Http\Controllers\SettingsTemplateController::class, 'setDefaultBody'])->name('settings.templates.body.default');

    Route::post('/settings/templates/signature', [\App\Http\Controllers\SettingsTemplateController::class, 'storeSignature'])->name('settings.templates.signature.store');
    Route::put('/settings/templates/signature/{signature}', [\App\Http\Controllers\SettingsTemplateController::class, 'updateSignature'])->name('settings.templates.signature.update');
    Route::delete('/settings/templates/signature/{signature}', [\App\Http\Controllers\SettingsTemplateController::class, 'destroySignature'])->name('settings.templates.signature.destroy');
    Route::post('/settings/templates/signature/{signature}/default', [\App\Http\Controllers\SettingsTemplateController::class, 'setDefaultSignature'])->name('settings.templates.signature.default');

    // Settings - Sender Identities CRUD
    Route::get('/settings/senders', [SenderIdentityController::class, 'index'])->name('settings.senders');
    Route::post('/settings/senders', [SenderIdentityController::class, 'store'])->name('settings.senders.store');
    Route::put('/settings/senders/{sender}', [SenderIdentityController::class, 'update'])->name('settings.senders.update');
    Route::delete('/settings/senders/{sender}', [SenderIdentityController::class, 'destroy'])->name('settings.senders.destroy');
    Route::post('/settings/senders/{sender}/default', [SenderIdentityController::class, 'setDefault'])->name('settings.senders.default');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
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
