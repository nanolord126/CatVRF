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
use App\Domains\Fashion\Http\Controllers\B2BFashionProductController;
use App\Domains\Fashion\Http\Controllers\FashionProductController;

Route::prefix('fashion')
    ->middleware(['correlation-id', 'auth:sanctum', 'tenant', 'rate-limit'])
    ->group(function () {
        Route::prefix('v1')->group(function () {
            Route::get('/', [FashionProductController::class, 'index']);
            Route::post('/', [FashionProductController::class, 'store']);
            Route::get('/{id}', [FashionProductController::class, 'show']);
            Route::put('/{id}', [FashionProductController::class, 'update']);
            Route::delete('/{id}', [FashionProductController::class, 'destroy']);
        });

        Route::prefix('b2b/v1')
            ->middleware(['b2b.api'])
            ->group(function () {
                Route::get('/catalog', [B2BFashionProductController::class, 'catalog']);
                Route::post('/bulk-order', [B2BFashionProductController::class, 'bulkOrder']);
            });
    });
