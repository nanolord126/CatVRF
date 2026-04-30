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
use App\Domains\ShortTermRentals\Http\Controllers\ApartmentBookingController;
use App\Domains\ShortTermRentals\Http\Controllers\B2BApartmentBookingController;

Route::prefix('short-term-rentals')
    ->middleware(['correlation-id', 'auth:sanctum', 'tenant', 'rate-limit'])
    ->group(function () {
        Route::prefix('v1')->group(function () {
            Route::get('/', [ApartmentBookingController::class, 'index']);
            Route::post('/', [ApartmentBookingController::class, 'store']);
            Route::get('/{id}', [ApartmentBookingController::class, 'show']);
            Route::put('/{id}', [ApartmentBookingController::class, 'update']);
            Route::delete('/{id}', [ApartmentBookingController::class, 'destroy']);
        });

        Route::prefix('b2b/v1')
            ->middleware(['b2b.api'])
            ->group(function () {
                Route::get('/catalog', [B2BApartmentBookingController::class, 'catalog']);
                Route::post('/bulk-order', [B2BApartmentBookingController::class, 'bulkOrder']);
            });
    });
