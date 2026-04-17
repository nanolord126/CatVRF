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

Route::prefix('electronics')
    ->middleware(['correlation-id', 'auth:sanctum', 'tenant', 'rate-limit'])
    ->group(function () {
        Route::prefix('v1')->group(function () {
            Route::get('/', [\App\Domains\Electronics\Http\Controllers\ElectronicsProductController::class, 'index']);
            Route::post('/', [\App\Domains\Electronics\Http\Controllers\ElectronicsProductController::class, 'store']);
            Route::get('/{id}', [\App\Domains\Electronics\Http\Controllers\ElectronicsProductController::class, 'show']);
            Route::put('/{id}', [\App\Domains\Electronics\Http\Controllers\ElectronicsProductController::class, 'update']);
            Route::delete('/{id}', [\App\Domains\Electronics\Http\Controllers\ElectronicsProductController::class, 'destroy']);

            Route::prefix('vision')->group(function () {
                Route::post('/analyze', [\App\Domains\Electronics\Http\Controllers\GadgetVisionController::class, 'analyze']);
                Route::post('/video-call', [\App\Domains\Electronics\Http\Controllers\GadgetVisionController::class, 'initiateVideoCall']);
            });

            Route::get('/products/{productId}/ar-model', [\App\Domains\Electronics\Http\Controllers\GadgetVisionController::class, 'getARModel']);
            Route::get('/products/{productId}/ar-qr', [\App\Domains\Electronics\Http\Controllers\GadgetVisionController::class, 'generateARQR']);

            Route::prefix('fraud')->group(function () {
                Route::post('/serial/validate', [\App\Domains\Electronics\Http\Controllers\FraudDetectionController::class, 'validateSerialNumber']);
                Route::post('/return/detect', [\App\Domains\Electronics\Http\Controllers\FraudDetectionController::class, 'detectReturnFraud']);
                Route::get('/statistics', [\App\Domains\Electronics\Http\Controllers\FraudDetectionController::class, 'getFraudStatistics']);
            });

            Route::prefix('wallet')->group(function () {
                Route::post('/split-payment', [\App\Domains\Electronics\Http\Controllers\WalletController::class, 'processSplitPayment']);
                Route::post('/escrow/release', [\App\Domains\Electronics\Http\Controllers\WalletController::class, 'releaseEscrow']);
                Route::get('/balance', [\App\Domains\Electronics\Http\Controllers\WalletController::class, 'getWalletBalance']);
                Route::get('/payments', [\App\Domains\Electronics\Http\Controllers\WalletController::class, 'getPaymentHistory']);
                Route::get('/escrow-holds', [\App\Domains\Electronics\Http\Controllers\WalletController::class, 'getEscrowHolds']);
            });

            Route::prefix('search')->group(function () {
                Route::get('/', [\App\Domains\Electronics\Http\Controllers\SearchController::class, 'search']);
                Route::get('/filters', [\App\Domains\Electronics\Http\Controllers\SearchController::class, 'getFilters']);
                Route::get('/suggestions', [\App\Domains\Electronics\Http\Controllers\SearchController::class, 'getSuggestions']);
                Route::get('/popular', [\App\Domains\Electronics\Http\Controllers\SearchController::class, 'getPopularSearches']);
            });
        });

        Route::prefix('b2b/v1')
            ->middleware(['b2b.api'])
            ->group(function () {
                Route::get('/catalog', [\App\Domains\Electronics\Http\Controllers\B2BElectronicsProductController::class, 'catalog']);
                Route::post('/bulk-order', [\App\Domains\Electronics\Http\Controllers\B2BElectronicsProductController::class, 'bulkOrder']);
            });
    });
