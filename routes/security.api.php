<?php

declare(strict_types=1);

use App\Http\Controllers\Api\RecoveryController;
use App\Http\Controllers\Api\DeviceManagementController;
use App\Http\Controllers\Api\SecurityController;
use App\Http\Controllers\Api\ZeroTrustSecurityController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Security API Routes
|--------------------------------------------------------------------------
|
| Account protection, recovery, and device management endpoints
|
*/

Route::middleware(['auth:sanctum'])->group(function () {
    // Security status and face verification
    Route::prefix('security')->group(function () {
        Route::get('/status', [SecurityController::class, 'status']);
        Route::post('/face', [SecurityController::class, 'storeFace']);
        Route::post('/face/verify', [SecurityController::class, 'verifyFace']);
        Route::get('/events', [SecurityController::class, 'events']);
    });

    // Zero-Trust Security (Employee Deprovisioning & Insider Threat)
    Route::prefix('zero-trust')->group(function () {
        // Employee deprovisioning
        Route::post('/tenants/{tenant}/employees/{employee}/revoke', [ZeroTrustSecurityController::class, 'revokeEmployee']);
        Route::post('/tenants/{tenant}/employees/{employee}/confirm', [ZeroTrustSecurityController::class, 'storeMultiOwnerConfirmation']);
        Route::post('/tenants/{tenant}/employees/{employee}/restore', [ZeroTrustSecurityController::class, 'restoreEmployee']);
        Route::get('/tenants/{tenant}/employees/{employee}/cooldown', [ZeroTrustSecurityController::class, 'checkCoolDown']);

        // Insider threat monitoring
        Route::get('/tenants/{tenant}/threats/summary', [ZeroTrustSecurityController::class, 'getThreatSummary']);
        Route::get('/tenants/{tenant}/threats/high-risk', [ZeroTrustSecurityController::class, 'getHighRiskThreats']);
        Route::get('/tenants/{tenant}/users/{user}/threat-profile', [ZeroTrustSecurityController::class, 'getUserThreatProfile']);
        Route::post('/threats/{threatLog}/review', [ZeroTrustSecurityController::class, 'reviewThreat']);
    });

    // Device management
    Route::prefix('devices')->group(function () {
        Route::get('/', [DeviceManagementController::class, 'index']);
        Route::get('/current', [DeviceManagementController::class, 'current']);
        Route::post('{deviceId}/revoke', [DeviceManagementController::class, 'revoke']);
        Route::post('{deviceId}/trust', [DeviceManagementController::class, 'trust']);
        Route::post('/revoke-all-others', [DeviceManagementController::class, 'revokeAllOthers']);
    });

    // Account recovery (requires authentication for backup codes)
    Route::prefix('recovery')->group(function () {
        Route::get('/backup-codes', [RecoveryController::class, 'getBackupCodes']);
        Route::post('/backup-codes/regenerate', [RecoveryController::class, 'regenerateBackupCodes']);
        Route::get('/history', [RecoveryController::class, 'history']);
    });
});

// Public recovery endpoints (no auth required, protected by brute-force)
Route::middleware('brute.force')->prefix('recovery')->group(function () {
    Route::post('/init', [RecoveryController::class, 'init']);
    Route::post('/{recoveryId}/verify', [RecoveryController::class, 'verify']);
    Route::post('/{recoveryId}/complete', [RecoveryController::class, 'complete']);
});
