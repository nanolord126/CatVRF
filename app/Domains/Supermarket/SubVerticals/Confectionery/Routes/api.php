<?php

declare(strict_types=1);

/**
 *  — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @version 2026.1
 *
 * @author CatVRF Team
 * @license Proprietary

 *
 * @see https://catvrf.ru/docs/component
 */


use Illuminate\Support\Facades\Route;
use App\Domains\Confectionery\Http\Controllers\B2BConfectioneryOrderController;
use App\Domains\Confectionery\Http\Controllers\ConfectioneryOrderController;

Route::prefix('confectionery')
    ->middleware(['correlation-id', 'auth:sanctum', 'tenant', 'rate-limit'])
    ->group(function () {
        Route::prefix('v1')->group(function () {
            Route::get('/', [ConfectioneryOrderController::class, 'index']);
            Route::post('/', [ConfectioneryOrderController::class, 'store']);
            Route::get('/{id}', [ConfectioneryOrderController::class, 'show']);
            Route::put('/{id}', [ConfectioneryOrderController::class, 'update']);
            Route::delete('/{id}', [ConfectioneryOrderController::class, 'destroy']);
        });

        Route::prefix('b2b/v1')
            ->middleware(['b2b.api'])
            ->group(function () {
                Route::get('/catalog', [B2BConfectioneryOrderController::class, 'catalog']);
                Route::post('/bulk-order', [B2BConfectioneryOrderController::class, 'bulkOrder']);
            });
    });
