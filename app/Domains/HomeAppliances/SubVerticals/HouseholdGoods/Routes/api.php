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
use App\Domains\HouseholdGoods\Http\Controllers\B2BHouseholdProductController;
use App\Domains\HouseholdGoods\Http\Controllers\HouseholdProductController;

Route::prefix('household-goods')
    ->middleware(['correlation-id', 'auth:sanctum', 'tenant', 'rate-limit'])
    ->group(function () {
        Route::prefix('v1')->group(function () {
            Route::get('/', [HouseholdProductController::class, 'index']);
            Route::post('/', [HouseholdProductController::class, 'store']);
            Route::get('/{id}', [HouseholdProductController::class, 'show']);
            Route::put('/{id}', [HouseholdProductController::class, 'update']);
            Route::delete('/{id}', [HouseholdProductController::class, 'destroy']);
        });

        Route::prefix('b2b/v1')
            ->middleware(['b2b.api'])
            ->group(function () {
                Route::get('/catalog', [B2BHouseholdProductController::class, 'catalog']);
                Route::post('/bulk-order', [B2BHouseholdProductController::class, 'bulkOrder']);
            });
    });
