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
 */


use Illuminate\Support\Facades\Route;
use App\Domains\GroceryAndDelivery\Http\Controllers\B2BGroceryOrderController;
use App\Domains\GroceryAndDelivery\Http\Controllers\GroceryOrderController;

Route::prefix('grocery-and-delivery')
    ->middleware(['correlation-id', 'auth:sanctum', 'tenant', 'rate-limit'])
    ->group(function () {
        Route::prefix('v1')->group(function () {
            Route::get('/', [GroceryOrderController::class, 'index']);
            Route::post('/', [GroceryOrderController::class, 'store']);
            Route::get('/{id}', [GroceryOrderController::class, 'show']);
            Route::put('/{id}', [GroceryOrderController::class, 'update']);
            Route::delete('/{id}', [GroceryOrderController::class, 'destroy']);
        });

        Route::prefix('b2b/v1')
            ->middleware(['b2b.api'])
            ->group(function () {
                Route::get('/catalog', [B2BGroceryOrderController::class, 'catalog']);
                Route::post('/bulk-order', [B2BGroceryOrderController::class, 'bulkOrder']);
            });
    });
