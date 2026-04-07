<?php

declare(strict_types=1);

/**
 * Advertising API Routes — CatVRF 2026.
 *
 * B2C: публичные endpoints для показа рекламы.
 * B2B: управление кампаниями через API-ключ.
 *
 * Middleware pipeline: correlation-id → auth:sanctum → tenant → rate-limit → fraud-check
 */

use Illuminate\Support\Facades\Route;

Route::prefix('advertising')
    ->middleware(['correlation-id', 'auth:sanctum', 'tenant', 'rate-limit'])
    ->group(function () {
        Route::prefix('v1')->group(function () {
            Route::get('/', [\App\Domains\Advertising\Http\Controllers\AdCampaignController::class, 'index']);
            Route::post('/', [\App\Domains\Advertising\Http\Controllers\AdCampaignController::class, 'store']);
            Route::get('/{id}', [\App\Domains\Advertising\Http\Controllers\AdCampaignController::class, 'show']);
            Route::put('/{id}', [\App\Domains\Advertising\Http\Controllers\AdCampaignController::class, 'update']);
            Route::delete('/{id}', [\App\Domains\Advertising\Http\Controllers\AdCampaignController::class, 'destroy']);
        });

        Route::prefix('b2b/v1')
            ->middleware(['b2b.api'])
            ->group(function () {
                Route::get('/catalog', [\App\Domains\Advertising\Http\Controllers\B2BAdCampaignController::class, 'catalog']);
                Route::post('/bulk-order', [\App\Domains\Advertising\Http\Controllers\B2BAdCampaignController::class, 'bulkOrder']);
            });
    });
