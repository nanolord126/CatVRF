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
            Route::get('/', [\App\Domains\Freelance\Http\Controllers\FreelanceOrderController::class, 'index']);
            Route::post('/', [\App\Domains\Freelance\Http\Controllers\FreelanceOrderController::class, 'store']);
            Route::get('/{id}', [\App\Domains\Freelance\Http\Controllers\FreelanceOrderController::class, 'show']);
            Route::put('/{id}', [\App\Domains\Freelance\Http\Controllers\FreelanceOrderController::class, 'update']);
            Route::delete('/{id}', [\App\Domains\Freelance\Http\Controllers\FreelanceOrderController::class, 'destroy']);
        });

        // B2B endpoints
        Route::prefix('b2b/v1')
            ->middleware(['b2b.api'])
            ->group(function () {
                Route::get('/catalog', [\App\Domains\Freelance\Http\Controllers\B2BFreelanceOrderController::class, 'catalog']);
                Route::post('/bulk-order', [\App\Domains\Freelance\Http\Controllers\B2BFreelanceOrderController::class, 'bulkOrder']);
            });
    });
