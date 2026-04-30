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
use App\Domains\VeganProducts\Http\Controllers\B2BVeganProductController;
use App\Domains\VeganProducts\Http\Controllers\VeganProductController;

Route::prefix('vegan-products')
    ->middleware(['correlation-id', 'auth:sanctum', 'tenant', 'rate-limit'])
    ->group(function () {
        Route::prefix('v1')->group(function () {
            Route::get('/', [VeganProductController::class, 'index']);
            Route::post('/', [VeganProductController::class, 'store']);
            Route::get('/{id}', [VeganProductController::class, 'show']);
            Route::put('/{id}', [VeganProductController::class, 'update']);
            Route::delete('/{id}', [VeganProductController::class, 'destroy']);
        });

        Route::prefix('b2b/v1')
            ->middleware(['b2b.api'])
            ->group(function () {
                Route::get('/catalog', [B2BVeganProductController::class, 'catalog']);
                Route::post('/bulk-order', [B2BVeganProductController::class, 'bulkOrder']);
            });
    });
