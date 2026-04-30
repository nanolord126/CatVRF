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
use App\Domains\Consulting\Http\Controllers\B2BConsultingSessionController;
use App\Domains\Consulting\Http\Controllers\ConsultingSessionController;

Route::prefix('consulting')
    ->middleware(['correlation-id', 'auth:sanctum', 'tenant', 'rate-limit'])
    ->group(function () {
        Route::prefix('v1')->group(function () {
            Route::get('/', [ConsultingSessionController::class, 'index']);
            Route::post('/', [ConsultingSessionController::class, 'store']);
            Route::get('/{id}', [ConsultingSessionController::class, 'show']);
            Route::put('/{id}', [ConsultingSessionController::class, 'update']);
            Route::delete('/{id}', [ConsultingSessionController::class, 'destroy']);
        });

        Route::prefix('b2b/v1')
            ->middleware(['b2b.api'])
            ->group(function () {
                Route::get('/catalog', [B2BConsultingSessionController::class, 'catalog']);
                Route::post('/bulk-order', [B2BConsultingSessionController::class, 'bulkOrder']);
            });
    });
