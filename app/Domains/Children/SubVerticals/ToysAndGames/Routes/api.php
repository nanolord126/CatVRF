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
use App\Domains\ToysAndGames\Http\Controllers\B2BToyProductController;
use App\Domains\ToysAndGames\Http\Controllers\ToyProductController;

Route::prefix('toys-and-games')
    ->middleware(['correlation-id', 'auth:sanctum', 'tenant', 'rate-limit'])
    ->group(function () {
        Route::prefix('v1')->group(function () {
            Route::get('/', [ToyProductController::class, 'index']);
            Route::post('/', [ToyProductController::class, 'store']);
            Route::get('/{id}', [ToyProductController::class, 'show']);
            Route::put('/{id}', [ToyProductController::class, 'update']);
            Route::delete('/{id}', [ToyProductController::class, 'destroy']);
        });

        Route::prefix('b2b/v1')
            ->middleware(['b2b.api'])
            ->group(function () {
                Route::get('/catalog', [B2BToyProductController::class, 'catalog']);
                Route::post('/bulk-order', [B2BToyProductController::class, 'bulkOrder']);
            });
    });
