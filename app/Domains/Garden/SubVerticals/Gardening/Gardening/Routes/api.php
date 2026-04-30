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
use App\Domains\Gardening\Http\Controllers\B2BGardeningOrderController;
use App\Domains\Gardening\Http\Controllers\GardeningOrderController;

Route::prefix('gardening')
    ->middleware(['correlation-id', 'auth:sanctum', 'tenant', 'rate-limit'])
    ->group(function () {
        Route::prefix('v1')->group(function () {
            Route::get('/', [GardeningOrderController::class, 'index']);
            Route::post('/', [GardeningOrderController::class, 'store']);
            Route::get('/{id}', [GardeningOrderController::class, 'show']);
            Route::put('/{id}', [GardeningOrderController::class, 'update']);
            Route::delete('/{id}', [GardeningOrderController::class, 'destroy']);
        });

        Route::prefix('b2b/v1')
            ->middleware(['b2b.api'])
            ->group(function () {
                Route::get('/catalog', [B2BGardeningOrderController::class, 'catalog']);
                Route::post('/bulk-order', [B2BGardeningOrderController::class, 'bulkOrder']);
            });
    });
