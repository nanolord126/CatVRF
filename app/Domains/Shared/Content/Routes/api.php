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
use App\Domains\Content\Http\Controllers\B2BContentPieceController;
use App\Domains\Content\Http\Controllers\ContentPieceController;

Route::prefix('content')
    ->middleware(['correlation-id', 'auth:sanctum', 'tenant', 'rate-limit'])
    ->group(function () {
        Route::prefix('v1')->group(function () {
            Route::get('/', [ContentPieceController::class, 'index']);
            Route::post('/', [ContentPieceController::class, 'store']);
            Route::get('/{id}', [ContentPieceController::class, 'show']);
            Route::put('/{id}', [ContentPieceController::class, 'update']);
            Route::delete('/{id}', [ContentPieceController::class, 'destroy']);
        });

        Route::prefix('b2b/v1')
            ->middleware(['b2b.api'])
            ->group(function () {
                Route::get('/catalog', [B2BContentPieceController::class, 'catalog']);
                Route::post('/bulk-order', [B2BContentPieceController::class, 'bulkOrder']);
            });
    });
