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

Route::prefix('pharmacy')
    ->middleware(['correlation-id', 'auth:sanctum', 'tenant', 'rate-limit'])
    ->group(function () {
        Route::prefix('v1')->group(function () {
            Route::get('/', [\App\Domains\Pharmacy\Http\Controllers\PharmacyOrderController::class, 'index']);
            Route::post('/', [\App\Domains\Pharmacy\Http\Controllers\PharmacyOrderController::class, 'store']);
            Route::get('/{id}', [\App\Domains\Pharmacy\Http\Controllers\PharmacyOrderController::class, 'show']);
            Route::put('/{id}', [\App\Domains\Pharmacy\Http\Controllers\PharmacyOrderController::class, 'update']);
            Route::delete('/{id}', [\App\Domains\Pharmacy\Http\Controllers\PharmacyOrderController::class, 'destroy']);
        });

        Route::prefix('b2b/v1')
            ->middleware(['b2b.api'])
            ->group(function () {
                Route::get('/catalog', [\App\Domains\Pharmacy\Http\Controllers\B2BPharmacyOrderController::class, 'catalog']);
                Route::post('/bulk-order', [\App\Domains\Pharmacy\Http\Controllers\B2BPharmacyOrderController::class, 'bulkOrder']);
            });
    });
