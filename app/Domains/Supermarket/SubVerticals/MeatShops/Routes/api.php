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
use App\Domains\MeatShops\Http\Controllers\B2BMeatOrderController;
use App\Domains\MeatShops\Http\Controllers\MeatOrderController;

Route::prefix('meat-shops')
    ->middleware(['correlation-id', 'auth:sanctum', 'tenant', 'rate-limit'])
    ->group(function () {
        Route::prefix('v1')->group(function () {
            Route::get('/', [MeatOrderController::class, 'index']);
            Route::post('/', [MeatOrderController::class, 'store']);
            Route::get('/{id}', [MeatOrderController::class, 'show']);
            Route::put('/{id}', [MeatOrderController::class, 'update']);
            Route::delete('/{id}', [MeatOrderController::class, 'destroy']);
        });

        Route::prefix('b2b/v1')
            ->middleware(['b2b.api'])
            ->group(function () {
                Route::get('/catalog', [B2BMeatOrderController::class, 'catalog']);
                Route::post('/bulk-order', [B2BMeatOrderController::class, 'bulkOrder']);
            });
    });
