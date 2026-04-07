<?php declare(strict_types=1);

/**
 *  — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/component
 */


use Illuminate\Support\Facades\Route;

Route::prefix('legal')
    ->middleware(['correlation-id', 'auth:sanctum', 'tenant', 'rate-limit'])
    ->group(function () {
        Route::prefix('v1')->group(function () {
            Route::get('/', [\App\Domains\Legal\Http\Controllers\LegalConsultationController::class, 'index']);
            Route::post('/', [\App\Domains\Legal\Http\Controllers\LegalConsultationController::class, 'store']);
            Route::get('/{id}', [\App\Domains\Legal\Http\Controllers\LegalConsultationController::class, 'show']);
            Route::put('/{id}', [\App\Domains\Legal\Http\Controllers\LegalConsultationController::class, 'update']);
            Route::delete('/{id}', [\App\Domains\Legal\Http\Controllers\LegalConsultationController::class, 'destroy']);
        });

        Route::prefix('b2b/v1')
            ->middleware(['b2b.api'])
            ->group(function () {
                Route::get('/catalog', [\App\Domains\Legal\Http\Controllers\B2BLegalConsultationController::class, 'catalog']);
                Route::post('/bulk-order', [\App\Domains\Legal\Http\Controllers\B2BLegalConsultationController::class, 'bulkOrder']);
            });
    });
