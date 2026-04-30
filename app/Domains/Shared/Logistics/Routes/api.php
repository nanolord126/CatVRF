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
use App\Domains\Logistics\Http\Controllers\B2BShipmentOrderController;
use App\Domains\Logistics\Http\Controllers\ShipmentOrderController;

Route::prefix('logistics')
    ->middleware(['correlation-id', 'auth:sanctum', 'tenant', 'rate-limit'])
    ->group(function () {
        Route::prefix('v1')->group(function () {
            Route::get('/', [ShipmentOrderController::class, 'index']);
            Route::post('/', [ShipmentOrderController::class, 'store']);
            Route::get('/{id}', [ShipmentOrderController::class, 'show']);
            Route::put('/{id}', [ShipmentOrderController::class, 'update']);
            Route::delete('/{id}', [ShipmentOrderController::class, 'destroy']);
        });

        Route::prefix('b2b/v1')
            ->middleware(['b2b.api'])
            ->group(function () {
                Route::get('/catalog', [B2BShipmentOrderController::class, 'catalog']);
                Route::post('/bulk-order', [B2BShipmentOrderController::class, 'bulkOrder']);
            });
    });
