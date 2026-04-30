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
use App\Domains\CleaningServices\Http\Controllers\B2BCleaningOrderController;
use App\Domains\CleaningServices\Http\Controllers\CleaningOrderController;

Route::prefix('cleaning-services')
    ->middleware(['correlation-id', 'auth:sanctum', 'tenant', 'rate-limit'])
    ->group(function () {
        Route::prefix('v1')->group(function () {
            Route::get('/', [CleaningOrderController::class, 'index']);
            Route::post('/', [CleaningOrderController::class, 'store']);
            Route::get('/{id}', [CleaningOrderController::class, 'show']);
            Route::put('/{id}', [CleaningOrderController::class, 'update']);
            Route::delete('/{id}', [CleaningOrderController::class, 'destroy']);
        });

        Route::prefix('b2b/v1')
            ->middleware(['b2b.api'])
            ->group(function () {
                Route::get('/catalog', [B2BCleaningOrderController::class, 'catalog']);
                Route::post('/bulk-order', [B2BCleaningOrderController::class, 'bulkOrder']);
            });
    });
