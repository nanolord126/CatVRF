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
use App\Domains\Sports\Http\Controllers\B2BSportVenueController;
use App\Domains\Sports\Http\Controllers\SportVenueController;

/*
|--------------------------------------------------------------------------
| Sports API Routes
|--------------------------------------------------------------------------
| Канон CatVRF 2026:
| - correlation-id middleware обязателен
| - auth:sanctum + tenant scoping
| - rate-limit на все endpoints
|--------------------------------------------------------------------------
*/

Route::prefix('sports')
    ->middleware(['correlation-id', 'auth:sanctum', 'tenant', 'rate-limit'])
    ->group(function () {

        // B2C endpoints
        Route::prefix('v1')->group(function () {
            Route::get('/', [SportVenueController::class, 'index']);
            Route::post('/', [SportVenueController::class, 'store']);
            Route::get('/{id}', [SportVenueController::class, 'show']);
            Route::put('/{id}', [SportVenueController::class, 'update']);
            Route::delete('/{id}', [SportVenueController::class, 'destroy']);
        });

        // B2B endpoints
        Route::prefix('b2b/v1')
            ->middleware(['b2b.api'])
            ->group(function () {
                Route::get('/catalog', [B2BSportVenueController::class, 'catalog']);
                Route::post('/bulk-order', [B2BSportVenueController::class, 'bulkOrder']);
            });
    });
