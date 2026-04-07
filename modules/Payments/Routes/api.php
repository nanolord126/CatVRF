<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Payments\Presentation\Http\Controllers\PaymentController;
use Modules\Payments\Presentation\Http\Controllers\WebhookController;

/*
|--------------------------------------------------------------------------
| Payments API Routes (Clean Architecture — 9-layer)
|--------------------------------------------------------------------------
*/

// ── Защищённые роуты ──────────────────────────────────────────────────────────
Route::middleware(['api', 'auth:sanctum', 'tenant'])
    ->prefix('payments')
    ->name('payments.')
    ->group(function (): void {
        // POST /api/payments/initiate
        Route::post('/initiate', [PaymentController::class, 'initiate'])
            ->name('initiate');

        // POST /api/payments/{id}/refund
        Route::post('/{id}/refund', [PaymentController::class, 'refund'])
            ->name('refund');
    });

// ── Webhook (без auth, проверка подписи внутри контроллера) ──────────────────
Route::middleware(['api', 'throttle:60,1'])
    ->prefix('payments/webhook')
    ->name('payments.webhook.')
    ->group(function (): void {
        Route::post('/tinkoff', [WebhookController::class, 'tinkoff'])
            ->name('tinkoff');
    });

