<?php

use App\Http\Controllers\N8nDeliveryCallbackController;
use Illuminate\Support\Facades\Route;

Route::middleware('n8n.webhook')->group(function () {
    Route::post('/webhooks/n8n/delivery-callback', N8nDeliveryCallbackController::class);
});
