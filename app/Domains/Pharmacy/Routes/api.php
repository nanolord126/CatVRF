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
use App\Domains\Pharmacy\Http\Controllers\B2BPharmacyOrderController;
use App\Domains\Pharmacy\Http\Controllers\PharmacyOrderController;

Route::prefix('pharmacy')
    ->middleware(['correlation-id', 'auth:sanctum', 'tenant', 'rate-limit'])
    ->group(function () {
        Route::prefix('v1')->group(function () {
            Route::get('/', [PharmacyOrderController::class, 'index']);
            Route::post('/', [PharmacyOrderController::class, 'store']);
            Route::get('/{id}', [PharmacyOrderController::class, 'show']);
            Route::put('/{id}', [PharmacyOrderController::class, 'update']);
            Route::delete('/{id}', [PharmacyOrderController::class, 'destroy']);
        });

        Route::prefix('b2b/v1')
            ->middleware(['b2b.api'])
            ->group(function () {
                Route::get('/catalog', [B2BPharmacyOrderController::class, 'catalog']);
                Route::post('/bulk-order', [B2BPharmacyOrderController::class, 'bulkOrder']);
            });
    });
