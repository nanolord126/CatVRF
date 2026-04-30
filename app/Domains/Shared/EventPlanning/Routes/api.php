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
use App\\Domains\\Shared\EventPlanning\Http\Controllers\B2BEventPlanController;
use App\\Domains\\Shared\EventPlanning\Http\Controllers\EventPlanController;

Route::prefix('event-planning')
    ->middleware(['correlation-id', 'auth:sanctum', 'tenant', 'rate-limit'])
    ->group(function () {
        Route::prefix('v1')->group(function () {
            Route::get('/', [EventPlanController::class, 'index']);
            Route::post('/', [EventPlanController::class, 'store']);
            Route::get('/{id}', [EventPlanController::class, 'show']);
            Route::put('/{id}', [EventPlanController::class, 'update']);
            Route::delete('/{id}', [EventPlanController::class, 'destroy']);
        });

        Route::prefix('b2b/v1')
            ->middleware(['b2b.api'])
            ->group(function () {
                Route::get('/catalog', [B2BEventPlanController::class, 'catalog']);
                Route::post('/bulk-order', [B2BEventPlanController::class, 'bulkOrder']);
            });
    });
