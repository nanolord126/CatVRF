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
use App\Domains\MusicAndInstruments\Http\Controllers\B2BMusicProductController;
use App\Domains\MusicAndInstruments\Http\Controllers\MusicProductController;

Route::prefix('music-and-instruments')
    ->middleware(['correlation-id', 'auth:sanctum', 'tenant', 'rate-limit'])
    ->group(function () {
        Route::prefix('v1')->group(function () {
            Route::get('/', [MusicProductController::class, 'index']);
            Route::post('/', [MusicProductController::class, 'store']);
            Route::get('/{id}', [MusicProductController::class, 'show']);
            Route::put('/{id}', [MusicProductController::class, 'update']);
            Route::delete('/{id}', [MusicProductController::class, 'destroy']);
        });

        Route::prefix('b2b/v1')
            ->middleware(['b2b.api'])
            ->group(function () {
                Route::get('/catalog', [B2BMusicProductController::class, 'catalog']);
                Route::post('/bulk-order', [B2BMusicProductController::class, 'bulkOrder']);
            });
    });
