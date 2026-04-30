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
use App\Domains\FarmDirect\Http\Controllers\B2BFarmProductController;
use App\Domains\FarmDirect\Http\Controllers\FarmProductController;

Route::prefix('farm-direct')
    ->middleware(['correlation-id', 'auth:sanctum', 'tenant', 'rate-limit'])
    ->group(function () {
        Route::prefix('v1')->group(function () {
            Route::get('/', [FarmProductController::class, 'index']);
            Route::post('/', [FarmProductController::class, 'store']);
            Route::get('/{id}', [FarmProductController::class, 'show']);
            Route::put('/{id}', [FarmProductController::class, 'update']);
            Route::delete('/{id}', [FarmProductController::class, 'destroy']);
        });

        Route::prefix('b2b/v1')
            ->middleware(['b2b.api'])
            ->group(function () {
                Route::get('/catalog', [B2BFarmProductController::class, 'catalog']);
                Route::post('/bulk-order', [B2BFarmProductController::class, 'bulkOrder']);
            });
    });
