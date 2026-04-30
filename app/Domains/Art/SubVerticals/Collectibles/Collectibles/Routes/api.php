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
use App\Domains\Collectibles\Http\Controllers\B2BCollectibleItemController;
use App\Domains\Collectibles\Http\Controllers\CollectibleItemController;

Route::prefix('collectibles')
    ->middleware(['correlation-id', 'auth:sanctum', 'tenant', 'rate-limit'])
    ->group(function () {
        Route::prefix('v1')->group(function () {
            Route::get('/', [CollectibleItemController::class, 'index']);
            Route::post('/', [CollectibleItemController::class, 'store']);
            Route::get('/{id}', [CollectibleItemController::class, 'show']);
            Route::put('/{id}', [CollectibleItemController::class, 'update']);
            Route::delete('/{id}', [CollectibleItemController::class, 'destroy']);
        });

        Route::prefix('b2b/v1')
            ->middleware(['b2b.api'])
            ->group(function () {
                Route::get('/catalog', [B2BCollectibleItemController::class, 'catalog']);
                Route::post('/bulk-order', [B2BCollectibleItemController::class, 'bulkOrder']);
            });
    });
