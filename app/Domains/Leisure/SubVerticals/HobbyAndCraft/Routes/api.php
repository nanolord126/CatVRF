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
use App\Domains\HobbyAndCraft\Http\Controllers\B2BCraftProductController;
use App\Domains\HobbyAndCraft\Http\Controllers\CraftProductController;

Route::prefix('hobby-and-craft')
    ->middleware(['correlation-id', 'auth:sanctum', 'tenant', 'rate-limit'])
    ->group(function () {
        Route::prefix('v1')->group(function () {
            Route::get('/', [CraftProductController::class, 'index']);
            Route::post('/', [CraftProductController::class, 'store']);
            Route::get('/{id}', [CraftProductController::class, 'show']);
            Route::put('/{id}', [CraftProductController::class, 'update']);
            Route::delete('/{id}', [CraftProductController::class, 'destroy']);
        });

        Route::prefix('b2b/v1')
            ->middleware(['b2b.api'])
            ->group(function () {
                Route::get('/catalog', [B2BCraftProductController::class, 'catalog']);
                Route::post('/bulk-order', [B2BCraftProductController::class, 'bulkOrder']);
            });
    });
