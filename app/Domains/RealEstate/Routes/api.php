<?php declare(strict_types=1);

/**
 *  — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/component
 */


use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| RealEstate API Routes
|--------------------------------------------------------------------------
| Канон CatVRF 2026:
| - correlation-id middleware обязателен
| - auth:sanctum + tenant scoping
| - rate-limit на все endpoints
|--------------------------------------------------------------------------
*/

Route::prefix('real-estate')
    ->middleware(['correlation-id', 'auth:sanctum', 'tenant', 'rate-limit'])
    ->group(function () {

        // B2C endpoints
        Route::prefix('v1')->group(function () {
            Route::get('/', [\App\Domains\RealEstate\Http\Controllers\PropertyController::class, 'index']);
            Route::post('/', [\App\Domains\RealEstate\Http\Controllers\PropertyController::class, 'store']);
            Route::get('/{id}', [\App\Domains\RealEstate\Http\Controllers\PropertyController::class, 'show']);
            Route::put('/{id}', [\App\Domains\RealEstate\Http\Controllers\PropertyController::class, 'update']);
            Route::delete('/{id}', [\App\Domains\RealEstate\Http\Controllers\PropertyController::class, 'destroy']);
        });

        // B2B endpoints
        Route::prefix('b2b/v1')
            ->middleware(['b2b.api'])
            ->group(function () {
                Route::get('/catalog', [\App\Domains\RealEstate\Http\Controllers\B2BPropertyController::class, 'catalog']);
                Route::post('/bulk-order', [\App\Domains\RealEstate\Http\Controllers\B2BPropertyController::class, 'bulkOrder']);
            });
    });
