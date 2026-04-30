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
use App\Domains\CarRental\Http\Controllers\B2BCarRentalBookingController;
use App\Domains\CarRental\Http\Controllers\CarRentalBookingController;

Route::prefix('car-rental')
    ->middleware(['correlation-id', 'auth:sanctum', 'tenant', 'rate-limit'])
    ->group(function () {
        Route::prefix('v1')->group(function () {
            Route::get('/', [CarRentalBookingController::class, 'index']);
            Route::post('/', [CarRentalBookingController::class, 'store']);
            Route::get('/{id}', [CarRentalBookingController::class, 'show']);
            Route::put('/{id}', [CarRentalBookingController::class, 'update']);
            Route::delete('/{id}', [CarRentalBookingController::class, 'destroy']);
        });

        Route::prefix('b2b/v1')
            ->middleware(['b2b.api'])
            ->group(function () {
                Route::get('/catalog', [B2BCarRentalBookingController::class, 'catalog']);
                Route::post('/bulk-order', [B2BCarRentalBookingController::class, 'bulkOrder']);
            });
    });
