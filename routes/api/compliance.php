<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ComplianceController;

/*
|--------------------------------------------------------------------------
| General Compliance API Routes
|--------------------------------------------------------------------------
|
| API endpoints for broader compliance features:
| - 152-ФЗ: Personal data compliance
| - Warehouse licenses management
| - Compliance integrations (Chestnyznak, EGISZ, OneC)
| - PII deletion requests
| - PII consents
|
*/

Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
    /*
    |--------------------------------------------------------------------------
    | 152-ФЗ Personal Data Compliance Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('compliance/152fz')->group(function () {
        Route::get('/check', [ComplianceController::class, 'checkCompliance']);
        Route::get('/audit-report', [ComplianceController::class, 'generateAuditReport']);
        Route::get('/checklist', [ComplianceController::class, 'getAuditChecklist']);
    });

    /*
    |--------------------------------------------------------------------------
    | Warehouse Licenses Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('compliance/warehouse-licenses')->group(function () {
        Route::get('/', [ComplianceController::class, 'getWarehouseLicenses']);
        Route::post('/', [ComplianceController::class, 'createWarehouseLicense']);
        Route::post('/{id}/revoke', [ComplianceController::class, 'revokeWarehouseLicense']);
    });

    /*
    |--------------------------------------------------------------------------
    | Compliance Integrations Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('compliance/integrations')->group(function () {
        Route::get('/', [ComplianceController::class, 'getIntegrations']);
        Route::get('/{type}/status', [ComplianceController::class, 'getIntegrationStatus']);
    });

    /*
    |--------------------------------------------------------------------------
    | PII Management Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('compliance/pii')->group(function () {
        Route::get('/deletion-requests', [ComplianceController::class, 'getPiiDeletionRequests']);
        Route::get('/consents', [ComplianceController::class, 'getPiiConsents']);
    });

    /*
    |--------------------------------------------------------------------------
    | Domain AML Routes (ФЗ-115)
    |--------------------------------------------------------------------------
    */
    Route::prefix('compliance/aml')->group(function () {
        Route::get('/check/{uuid}', [ComplianceController::class, 'getDomainAMLCheck']);
        Route::get('/user/{userId}', [ComplianceController::class, 'getDomainUserAMLChecks']);
        Route::get('/pending', [ComplianceController::class, 'getDomainPendingAMLChecks']);
        Route::get('/stats', [ComplianceController::class, 'getDomainAMLStats']);
        Route::get('/high-risk-users', [ComplianceController::class, 'getHighRiskAMLUsers']);
        Route::get('/suspicious', [ComplianceController::class, 'getSuspiciousOperations']);
    });

    /*
    |--------------------------------------------------------------------------
    | Dashboard Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('compliance/dashboard')->group(function () {
        Route::get('/stats', [ComplianceController::class, 'getDashboardStats']);
    });
});
