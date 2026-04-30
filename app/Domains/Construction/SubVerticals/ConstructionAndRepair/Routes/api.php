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
use App\Domains\ConstructionAndRepair\Http\Controllers\B2BRepairProjectController;
use App\Domains\ConstructionAndRepair\Http\Controllers\RepairProjectController;

Route::prefix('construction-and-repair')
    ->middleware(['correlation-id', 'auth:sanctum', 'tenant', 'rate-limit'])
    ->group(function () {
        Route::prefix('v1')->group(function () {
            Route::get('/', [RepairProjectController::class, 'index']);
            Route::post('/', [RepairProjectController::class, 'store']);
            Route::get('/{id}', [RepairProjectController::class, 'show']);
            Route::put('/{id}', [RepairProjectController::class, 'update']);
            Route::delete('/{id}', [RepairProjectController::class, 'destroy']);
        });

        Route::prefix('b2b/v1')
            ->middleware(['b2b.api'])
            ->group(function () {
                Route::get('/catalog', [B2BRepairProjectController::class, 'catalog']);
                Route::post('/bulk-order', [B2BRepairProjectController::class, 'bulkOrder']);
            });
    });
