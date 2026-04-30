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
use App\Domains\Luxury\Http\Controllers\B2BLuxuryBookingController;
use App\Domains\Luxury\Http\Controllers\LuxuryBookingController;

Route::prefix('luxury')
    ->middleware(['correlation-id', 'auth:sanctum', 'tenant', 'rate-limit'])
    ->group(function () {
        Route::prefix('v1')->group(function () {
            Route::get('/', [LuxuryBookingController::class, 'index']);
            Route::post('/', [LuxuryBookingController::class, 'store']);
            Route::get('/{id}', [LuxuryBookingController::class, 'show']);
            Route::put('/{id}', [LuxuryBookingController::class, 'update']);
            Route::delete('/{id}', [LuxuryBookingController::class, 'destroy']);
        });

        Route::prefix('b2b/v1')
            ->middleware(['b2b.api'])
            ->group(function () {
                Route::get('/catalog', [B2BLuxuryBookingController::class, 'catalog']);
                Route::post('/bulk-order', [B2BLuxuryBookingController::class, 'bulkOrder']);
            });
    });
