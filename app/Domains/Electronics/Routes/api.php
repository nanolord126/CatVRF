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
use App\Domains\Electronics\Http\Controllers\B2BElectronicsProductController;
use App\Domains\Electronics\Http\Controllers\ElectronicsProductController;
use App\Domains\Electronics\Http\Controllers\FraudDetectionController;
use App\Domains\Electronics\Http\Controllers\GadgetVisionController;
use App\Domains\Electronics\Http\Controllers\SearchController;
use App\Domains\Electronics\Http\Controllers\WalletController;

Route::prefix('electronics')
    ->middleware(['correlation-id', 'auth:sanctum', 'tenant', 'rate-limit'])
    ->group(function () {
        Route::prefix('v1')->group(function () {
            Route::get('/', [ElectronicsProductController::class, 'index']);
            Route::post('/', [ElectronicsProductController::class, 'store']);
            Route::get('/{id}', [ElectronicsProductController::class, 'show']);
            Route::put('/{id}', [ElectronicsProductController::class, 'update']);
            Route::delete('/{id}', [ElectronicsProductController::class, 'destroy']);

            Route::prefix('vision')->group(function () {
                Route::post('/analyze', [GadgetVisionController::class, 'analyze']);
                Route::post('/video-call', [GadgetVisionController::class, 'initiateVideoCall']);
            });

            Route::get('/products/{productId}/ar-model', [GadgetVisionController::class, 'getARModel']);
            Route::get('/products/{productId}/ar-qr', [GadgetVisionController::class, 'generateARQR']);

            Route::prefix('fraud')->group(function () {
                Route::post('/serial/validate', [FraudDetectionController::class, 'validateSerialNumber']);
                Route::post('/return/detect', [FraudDetectionController::class, 'detectReturnFraud']);
                Route::get('/statistics', [FraudDetectionController::class, 'getFraudStatistics']);
            });

            Route::prefix('wallet')->group(function () {
                Route::post('/split-payment', [WalletController::class, 'processSplitPayment']);
                Route::post('/escrow/release', [WalletController::class, 'releaseEscrow']);
                Route::get('/balance', [WalletController::class, 'getWalletBalance']);
                Route::get('/payments', [WalletController::class, 'getPaymentHistory']);
                Route::get('/escrow-holds', [WalletController::class, 'getEscrowHolds']);
            });

            Route::prefix('search')->group(function () {
                Route::get('/', [SearchController::class, 'search']);
                Route::get('/filters', [SearchController::class, 'getFilters']);
                Route::get('/suggestions', [SearchController::class, 'getSuggestions']);
                Route::get('/popular', [SearchController::class, 'getPopularSearches']);
            });
        });

        Route::prefix('b2b/v1')
            ->middleware(['b2b.api'])
            ->group(function () {
                Route::get('/catalog', [B2BElectronicsProductController::class, 'catalog']);
                Route::post('/bulk-order', [B2BElectronicsProductController::class, 'bulkOrder']);
            });
    });
