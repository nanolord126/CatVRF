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
use App\Domains\Furniture\Http\Controllers\B2BFurnitureOrderController;
use App\Domains\Furniture\Http\Controllers\FurnitureOrderController;

Route::prefix('furniture')
    ->middleware(['correlation-id', 'auth:sanctum', 'tenant', 'rate-limit'])
    ->group(function () {
        Route::prefix('v1')->group(function () {
            Route::get('/', [FurnitureOrderController::class, 'index']);
            Route::post('/', [FurnitureOrderController::class, 'store']);
            Route::get('/{id}', [FurnitureOrderController::class, 'show']);
            Route::put('/{id}', [FurnitureOrderController::class, 'update']);
            Route::delete('/{id}', [FurnitureOrderController::class, 'destroy']);
        });

        Route::prefix('b2b/v1')
            ->middleware(['b2b.api'])
            ->group(function () {
                Route::get('/catalog', [B2BFurnitureOrderController::class, 'catalog']);
                Route::post('/bulk-order', [B2BFurnitureOrderController::class, 'bulkOrder']);
            });
    });
