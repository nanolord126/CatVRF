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
use App\Domains\Insurance\Http\Controllers\B2BInsurancePolicyController;
use App\Domains\Insurance\Http\Controllers\InsurancePolicyController;

Route::prefix('insurance')
    ->middleware(['correlation-id', 'auth:sanctum', 'tenant', 'rate-limit'])
    ->group(function () {
        Route::prefix('v1')->group(function () {
            Route::get('/', [InsurancePolicyController::class, 'index']);
            Route::post('/', [InsurancePolicyController::class, 'store']);
            Route::get('/{id}', [InsurancePolicyController::class, 'show']);
            Route::put('/{id}', [InsurancePolicyController::class, 'update']);
            Route::delete('/{id}', [InsurancePolicyController::class, 'destroy']);
        });

        Route::prefix('b2b/v1')
            ->middleware(['b2b.api'])
            ->group(function () {
                Route::get('/catalog', [B2BInsurancePolicyController::class, 'catalog']);
                Route::post('/bulk-order', [B2BInsurancePolicyController::class, 'bulkOrder']);
            });
    });
