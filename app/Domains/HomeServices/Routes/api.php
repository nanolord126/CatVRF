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
use App\Domains\HomeServices\Http\Controllers\B2BHomeServiceJobController;
use App\Domains\HomeServices\Http\Controllers\HomeServiceJobController;

/*
|--------------------------------------------------------------------------
| HomeServices API Routes
|--------------------------------------------------------------------------
| Канон CatVRF 2026:
| - correlation-id middleware обязателен
| - auth:sanctum + tenant scoping
| - rate-limit на все endpoints
|--------------------------------------------------------------------------
*/

Route::prefix('home-services')
    ->middleware(['correlation-id', 'auth:sanctum', 'tenant', 'rate-limit'])
    ->group(function () {

        // B2C endpoints
        Route::prefix('v1')->group(function () {
            Route::get('/', [HomeServiceJobController::class, 'index']);
            Route::post('/', [HomeServiceJobController::class, 'store']);
            Route::get('/{id}', [HomeServiceJobController::class, 'show']);
            Route::put('/{id}', [HomeServiceJobController::class, 'update']);
            Route::delete('/{id}', [HomeServiceJobController::class, 'destroy']);
        });

        // B2B endpoints
        Route::prefix('b2b/v1')
            ->middleware(['b2b.api'])
            ->group(function () {
                Route::get('/catalog', [B2BHomeServiceJobController::class, 'catalog']);
                Route::post('/bulk-order', [B2BHomeServiceJobController::class, 'bulkOrder']);
            });
    });
