<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TelegramController;

/*
|--------------------------------------------------------------------------
| Telegram Webhook Routes
|--------------------------------------------------------------------------
|
| Routes for Telegram bot webhook and management
|
*/

Route::prefix('telegram')->group(function () {
    // Webhook endpoint for Telegram updates
    Route::post('/webhook', [TelegramController::class, 'webhook'])
        ->name('telegram.webhook');

    // Management endpoints (protected)
    Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
        Route::post('/generate-link', [TelegramController::class, 'generateLink'])
            ->name('telegram.generate-link');

        Route::post('/set-webhook', [TelegramController::class, 'setWebhook'])
            ->name('telegram.set-webhook');

        Route::get('/webhook-info', [TelegramController::class, 'getWebhookInfo'])
            ->name('telegram.webhook-info');

        Route::post('/delete-webhook', [TelegramController::class, 'deleteWebhook'])
            ->name('telegram.delete-webhook');
    });
});
