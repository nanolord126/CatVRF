<?php

declare(strict_types=1);

use App\Http\Controllers\WhatsAppWebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/whatsapp')->group(function () {
    // Webhook endpoint for WhatsApp messages
    Route::match(['get', 'post'], '/webhook', [WhatsAppWebhookController::class, 'webhook'])
        ->name('whatsapp.webhook');
});
