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
use App\Domains\PersonalDevelopment\Http\Controllers\B2BCoachingSessionController;
use App\Domains\PersonalDevelopment\Http\Controllers\CoachingSessionController;

Route::prefix('personal-development')
    ->middleware(['correlation-id', 'auth:sanctum', 'tenant', 'rate-limit'])
    ->group(function () {
        Route::prefix('v1')->group(function () {
            Route::get('/', [CoachingSessionController::class, 'index']);
            Route::post('/', [CoachingSessionController::class, 'store']);
            Route::get('/{id}', [CoachingSessionController::class, 'show']);
            Route::put('/{id}', [CoachingSessionController::class, 'update']);
            Route::delete('/{id}', [CoachingSessionController::class, 'destroy']);
        });

        Route::prefix('b2b/v1')
            ->middleware(['b2b.api'])
            ->group(function () {
                Route::get('/catalog', [B2BCoachingSessionController::class, 'catalog']);
                Route::post('/bulk-order', [B2BCoachingSessionController::class, 'bulkOrder']);
            });
    });
