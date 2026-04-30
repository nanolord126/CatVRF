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
use App\Domains\PartySupplies\Http\Controllers\B2BPartyOrderController;
use App\Domains\PartySupplies\Http\Controllers\PartyOrderController;

Route::prefix('party-supplies')
    ->middleware(['correlation-id', 'auth:sanctum', 'tenant', 'rate-limit'])
    ->group(function () {
        Route::prefix('v1')->group(function () {
            Route::get('/', [PartyOrderController::class, 'index']);
            Route::post('/', [PartyOrderController::class, 'store']);
            Route::get('/{id}', [PartyOrderController::class, 'show']);
            Route::put('/{id}', [PartyOrderController::class, 'update']);
            Route::delete('/{id}', [PartyOrderController::class, 'destroy']);
        });

        Route::prefix('b2b/v1')
            ->middleware(['b2b.api'])
            ->group(function () {
                Route::get('/catalog', [B2BPartyOrderController::class, 'catalog']);
                Route::post('/bulk-order', [B2BPartyOrderController::class, 'bulkOrder']);
            });
    });
