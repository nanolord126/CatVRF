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
use App\Domains\Legal\Http\Controllers\B2BLegalConsultationController;
use App\Domains\Legal\Http\Controllers\LegalConsultationController;

Route::prefix('legal')
    ->middleware(['correlation-id', 'auth:sanctum', 'tenant', 'rate-limit'])
    ->group(function () {
        Route::prefix('v1')->group(function () {
            Route::get('/', [LegalConsultationController::class, 'index']);
            Route::post('/', [LegalConsultationController::class, 'store']);
            Route::get('/{id}', [LegalConsultationController::class, 'show']);
            Route::put('/{id}', [LegalConsultationController::class, 'update']);
            Route::delete('/{id}', [LegalConsultationController::class, 'destroy']);
        });

        Route::prefix('b2b/v1')
            ->middleware(['b2b.api'])
            ->group(function () {
                Route::get('/catalog', [B2BLegalConsultationController::class, 'catalog']);
                Route::post('/bulk-order', [B2BLegalConsultationController::class, 'bulkOrder']);
            });
    });
