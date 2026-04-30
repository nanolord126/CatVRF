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
use App\Domains\SportsNutrition\Http\Controllers\B2BNutritionOrderController;
use App\Domains\SportsNutrition\Http\Controllers\NutritionOrderController;

Route::prefix('sports-nutrition')
    ->middleware(['correlation-id', 'auth:sanctum', 'tenant', 'rate-limit'])
    ->group(function () {
        Route::prefix('v1')->group(function () {
            Route::get('/', [NutritionOrderController::class, 'index']);
            Route::post('/', [NutritionOrderController::class, 'store']);
            Route::get('/{id}', [NutritionOrderController::class, 'show']);
            Route::put('/{id}', [NutritionOrderController::class, 'update']);
            Route::delete('/{id}', [NutritionOrderController::class, 'destroy']);
        });

        Route::prefix('b2b/v1')
            ->middleware(['b2b.api'])
            ->group(function () {
                Route::get('/catalog', [B2BNutritionOrderController::class, 'catalog']);
                Route::post('/bulk-order', [B2BNutritionOrderController::class, 'bulkOrder']);
            });
    });
