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
use App\Domains\Advertising\Http\Controllers\AdCampaignController;

Route::prefix('advertising')
    ->middleware(['correlation-id', 'auth:sanctum', 'tenant', 'rate-limit'])
    ->group(function () {
        Route::prefix('v1')->group(function () {
            Route::get('/', [AdCampaignController::class, 'index']);
            Route::post('/', [AdCampaignController::class, 'store']);
            Route::get('/{id}', [AdCampaignController::class, 'show']);
            Route::put('/{id}', [AdCampaignController::class, 'update']);
            Route::delete('/{id}', [AdCampaignController::class, 'destroy']);
        });

        // B2B routes - controller to be implemented
        Route::prefix('b2b/v1')
            ->middleware(['b2b.api'])
            ->group(function () {
                Route::get('/catalog', [AdCampaignController::class, 'index']);
                Route::post('/bulk-order', [AdCampaignController::class, 'store']);
            });
    });
