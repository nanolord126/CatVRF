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

Route::prefix('veterinary')
    ->middleware(['correlation-id', 'auth:sanctum', 'tenant', 'rate-limit'])
    ->group(function () {
        Route::prefix('v1')->group(function () {
            Route::get('/', [\App\Domains\Veterinary\Http\Controllers\VetAppointmentController::class, 'index']);
            Route::post('/', [\App\Domains\Veterinary\Http\Controllers\VetAppointmentController::class, 'store']);
            Route::get('/{id}', [\App\Domains\Veterinary\Http\Controllers\VetAppointmentController::class, 'show']);
            Route::put('/{id}', [\App\Domains\Veterinary\Http\Controllers\VetAppointmentController::class, 'update']);
            Route::delete('/{id}', [\App\Domains\Veterinary\Http\Controllers\VetAppointmentController::class, 'destroy']);
        });

        Route::prefix('b2b/v1')
            ->middleware(['b2b.api'])
            ->group(function () {
                Route::get('/catalog', [\App\Domains\Veterinary\Http\Controllers\B2BVetAppointmentController::class, 'catalog']);
                Route::post('/bulk-order', [\App\Domains\Veterinary\Http\Controllers\B2BVetAppointmentController::class, 'bulkOrder']);
            });
    });
