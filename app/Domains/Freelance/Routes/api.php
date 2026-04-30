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
use App\Domains\Freelance\Http\Controllers\B2BFreelanceOrderController;
use App\Domains\Freelance\Http\Controllers\FreelanceOrderController;

/*
|--------------------------------------------------------------------------
| Freelance API Routes
|--------------------------------------------------------------------------
| Канон CatVRF 2026:
| - correlation-id middleware обязателен
| - auth:sanctum + tenant scoping
| - rate-limit на все endpoints
|--------------------------------------------------------------------------
*/

Route::prefix('freelance')
    ->middleware(['correlation-id', 'auth:sanctum', 'tenant', 'rate-limit'])
    ->group(function () {

        // B2C endpoints
        Route::prefix('v1')->group(function () {
            Route::get('/', [FreelanceOrderController::class, 'index']);
            Route::post('/', [FreelanceOrderController::class, 'store']);
            Route::get('/{id}', [FreelanceOrderController::class, 'show']);
            Route::put('/{id}', [FreelanceOrderController::class, 'update']);
            Route::delete('/{id}', [FreelanceOrderController::class, 'destroy']);
        });

        // B2B endpoints
        Route::prefix('b2b/v1')
            ->middleware(['b2b.api'])
            ->group(function () {
                Route::get('/catalog', [B2BFreelanceOrderController::class, 'catalog']);
                Route::post('/bulk-order', [B2BFreelanceOrderController::class, 'bulkOrder']);
            });
    });
