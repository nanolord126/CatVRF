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
use App\Domains\Fitness\Http\Controllers\B2BFitnessBookingController;
use App\Domains\Fitness\Http\Controllers\FitnessBookingController;

Route::prefix('fitness')
    ->middleware(['correlation-id', 'auth:sanctum', 'tenant', 'rate-limit'])
    ->group(function () {
        Route::prefix('v1')->group(function () {
            Route::get('/', [FitnessBookingController::class, 'index']);
            Route::post('/', [FitnessBookingController::class, 'store']);
            Route::get('/{id}', [FitnessBookingController::class, 'show']);
            Route::put('/{id}', [FitnessBookingController::class, 'update']);
            Route::delete('/{id}', [FitnessBookingController::class, 'destroy']);
        });

        Route::prefix('b2b/v1')
            ->middleware(['b2b.api'])
            ->group(function () {
                Route::get('/catalog', [B2BFitnessBookingController::class, 'catalog']);
                Route::post('/bulk-order', [B2BFitnessBookingController::class, 'bulkOrder']);
            });
    });
