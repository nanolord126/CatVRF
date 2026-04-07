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
| Medical API Routes
|--------------------------------------------------------------------------
| Канон CatVRF 2026:
| - correlation-id middleware обязателен
| - auth:sanctum + tenant scoping
| - rate-limit на все endpoints
|--------------------------------------------------------------------------
*/

Route::prefix('medical')
    ->middleware(['correlation-id', 'auth:sanctum', 'tenant', 'rate-limit'])
    ->group(function () {

        // B2C endpoints
        Route::prefix('v1')->group(function () {
            Route::get('/', [\App\Domains\Medical\Http\Controllers\AppointmentController::class, 'index']);
            Route::post('/', [\App\Domains\Medical\Http\Controllers\AppointmentController::class, 'store']);
            Route::get('/{id}', [\App\Domains\Medical\Http\Controllers\AppointmentController::class, 'show']);
            Route::put('/{id}', [\App\Domains\Medical\Http\Controllers\AppointmentController::class, 'update']);
            Route::delete('/{id}', [\App\Domains\Medical\Http\Controllers\AppointmentController::class, 'destroy']);
        });

        // B2B endpoints
        Route::prefix('b2b/v1')
            ->middleware(['b2b.api'])
            ->group(function () {
                Route::get('/catalog', [\App\Domains\Medical\Http\Controllers\B2BAppointmentController::class, 'catalog']);
                Route::post('/bulk-order', [\App\Domains\Medical\Http\Controllers\B2BAppointmentController::class, 'bulkOrder']);
            });
    });
