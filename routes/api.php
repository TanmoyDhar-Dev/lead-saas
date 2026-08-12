<?php

use App\Http\Controllers\GraphWebhookController;
use App\Http\Controllers\ImportedLeadOutreachController;
use App\Http\Controllers\N8nDeliveryCallbackController;
use Illuminate\Support\Facades\Route;

Route::middleware('n8n.webhook')->group(function () {
    Route::post('/webhooks/n8n/delivery-callback', N8nDeliveryCallbackController::class);
});

// Microsoft Graph change notifications (no auth middleware — secured via clientState).
Route::match(['get', 'post'], '/webhooks/graph/notifications', GraphWebhookController::class)
    ->name('webhooks.graph.notifications');

// Session-authenticated JSON API (web middleware for cookie auth; apiPrefix is empty).
Route::middleware(['web', 'auth', 'verified', 'active_user'])->group(function () {
    Route::get('/api/imported-outreach/campaigns', [ImportedLeadOutreachController::class, 'campaigns'])
        ->name('api.imported-outreach.campaigns');
    Route::get('/api/imported-outreach/{importedOutreach}/recipients', [ImportedLeadOutreachController::class, 'campaignRecipients'])
        ->name('api.imported-outreach.recipients');
    Route::post('/api/imported-outreach/bulk-reply', [ImportedLeadOutreachController::class, 'bulkReply'])
        ->name('api.imported-outreach.bulk-reply');
});
