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
use App\Domains\Hotels\Http\Controllers\B2BHotelBookingController;
use App\Domains\Hotels\Http\Controllers\HotelBookingController;

Route::prefix('hotels')
    ->middleware(['correlation-id', 'auth:sanctum', 'tenant', 'rate-limit'])
    ->group(function () {
        Route::prefix('v1')->group(function () {
            Route::get('/', [HotelBookingController::class, 'index']);
            Route::post('/', [HotelBookingController::class, 'store']);
            Route::get('/{id}', [HotelBookingController::class, 'show']);
            Route::put('/{id}', [HotelBookingController::class, 'update']);
            Route::delete('/{id}', [HotelBookingController::class, 'destroy']);
        });

        Route::prefix('b2b/v1')
            ->middleware(['b2b.api'])
            ->group(function () {
                Route::get('/catalog', [B2BHotelBookingController::class, 'catalog']);
                Route::post('/bulk-order', [B2BHotelBookingController::class, 'bulkOrder']);
            });
    });
