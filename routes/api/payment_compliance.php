<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PaymentComplianceController;

/*
|--------------------------------------------------------------------------
| Payment Compliance API Routes
|--------------------------------------------------------------------------
|
| API endpoints for federal law compliance features:
| - ФЗ-161: Payment rules validation and management
| - ФЗ-115: AML/KYC checks and monitoring
| - 54-ФЗ: Fiscal receipts management
|
*/

Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
    /*
    |--------------------------------------------------------------------------
    | ФЗ-161 Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('compliance/fz161')->group(function () {
        Route::post('/validate', [PaymentComplianceController::class, 'validateFZ161']);
        Route::get('/rules', [PaymentComplianceController::class, 'getPaymentRules']);
        Route::get('/rules/{code}/history', [PaymentComplianceController::class, 'getRuleHistory']);
        Route::get('/rules/export', [PaymentComplianceController::class, 'exportRules']);
    });

    /*
    |--------------------------------------------------------------------------
    | ФЗ-115 Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('compliance/fz115')->group(function () {
        Route::get('/aml/{uuid}', [PaymentComplianceController::class, 'getAMLCheck']);
        Route::get('/aml/user/{userId}', [PaymentComplianceController::class, 'getUserAMLChecks']);
        Route::get('/aml/pending', [PaymentComplianceController::class, 'getPendingAMLChecks']);
        Route::post('/aml/{uuid}/mark-reported', [PaymentComplianceController::class, 'markAMLAsReported']);
    });

    /*
    |--------------------------------------------------------------------------
    | 54-ФЗ Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('compliance/fz54')->group(function () {
        Route::get('/fiscal/{uuid}', [PaymentComplianceController::class, 'getFiscalReceipt']);
        Route::get('/fiscal/payment/{paymentIntentUuid}', [PaymentComplianceController::class, 'getPaymentIntentReceipts']);
        Route::get('/fiscal/pending', [PaymentComplianceController::class, 'getPendingFiscalReceipts']);
        Route::post('/fiscal/{uuid}/retry', [PaymentComplianceController::class, 'retryFiscalReceipt']);
    });

    /*
    |--------------------------------------------------------------------------
    | Dashboard Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('compliance/dashboard')->group(function () {
        Route::get('/stats', [PaymentComplianceController::class, 'getDashboardStats']);
        Route::get('/trends', [PaymentComplianceController::class, 'getComplianceTrends']);
    });
});
