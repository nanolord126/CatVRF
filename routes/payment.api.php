<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Payment\PaymentController;

/**
 * Payment Gateway API Routes v1
 * Production 2026.03.24
 */

// ===== AUTHENTICATED ENDPOINTS (Auth) =====
Route::middleware(['api', 'auth:sanctum', 'tenant', 'throttle:60,1'])->prefix('api/v1/payments')->group(function () {
    // Payment initialization
    Route::post('/init', [PaymentController::class, 'init'])
        ->name('api.payments.init')
        ->middleware('throttle:30,1');
    
    // Payment capture
    Route::post('/{payment}/capture', [PaymentController::class, 'capture'])
        ->name('api.payments.capture')
        ->middleware('throttle:30,1');
    
    // Payment refund
    Route::post('/{payment}/refund', [PaymentController::class, 'refund'])
        ->name('api.payments.refund')
        ->middleware('throttle:20,1');
    
    // Payment status check
    Route::get('/{payment}', [PaymentController::class, 'show'])
        ->name('api.payments.show');
});

// ===== WEBHOOK ROUTES (No Auth, IP Whitelisted) =====
Route::middleware(['throttle:100,1', 'ip-whitelist:payment-gateways'])->prefix('api/v1/webhooks')->group(function () {
    // Tinkoff webhook
    Route::post('/tinkoff', [PaymentController::class, 'webhookTinkoff'])
        ->name('api.webhook.tinkoff');
    
    // Tochka Bank webhook
    Route::post('/tochka', [PaymentController::class, 'webhookTochka'])
        ->name('api.webhook.tochka');
    
    // Sber webhook
    Route::post('/sber', [PaymentController::class, 'webhookSber'])
        ->name('api.webhook.sber');
});
