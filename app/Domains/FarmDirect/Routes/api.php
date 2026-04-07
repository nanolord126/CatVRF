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

Route::prefix('farm-direct')
    ->middleware(['correlation-id', 'auth:sanctum', 'tenant', 'rate-limit'])
    ->group(function () {
        Route::prefix('v1')->group(function () {
            Route::get('/', [\App\Domains\FarmDirect\Http\Controllers\FarmProductController::class, 'index']);
            Route::post('/', [\App\Domains\FarmDirect\Http\Controllers\FarmProductController::class, 'store']);
            Route::get('/{id}', [\App\Domains\FarmDirect\Http\Controllers\FarmProductController::class, 'show']);
            Route::put('/{id}', [\App\Domains\FarmDirect\Http\Controllers\FarmProductController::class, 'update']);
            Route::delete('/{id}', [\App\Domains\FarmDirect\Http\Controllers\FarmProductController::class, 'destroy']);
        });

        Route::prefix('b2b/v1')
            ->middleware(['b2b.api'])
            ->group(function () {
                Route::get('/catalog', [\App\Domains\FarmDirect\Http\Controllers\B2BFarmProductController::class, 'catalog']);
                Route::post('/bulk-order', [\App\Domains\FarmDirect\Http\Controllers\B2BFarmProductController::class, 'bulkOrder']);
            });
    });
