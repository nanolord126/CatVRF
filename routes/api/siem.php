<?php

declare(strict_types=1);

use App\Http\Controllers\Api\SIEMController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    // SIEM Dashboard endpoints
    Route::get('/summary', [SIEMController::class, 'summary']);
    Route::get('/recent', [SIEMController::class, 'recent']);
    Route::get('/timeline', [SIEMController::class, 'timeline']);
    Route::get('/attack-chain/{correlationId}', [SIEMController::class, 'attackChain']);
    Route::get('/high-risk-tenants', [SIEMController::class, 'highRiskTenants']);
    Route::get('/metrics', [SIEMController::class, 'metrics']);
    Route::get('/user-events', [SIEMController::class, 'userEvents']);
    Route::get('/tenant-events', [SIEMController::class, 'tenantEvents']);

    // Security event management
    Route::post('/events/{eventId}/resolve', [SIEMController::class, 'resolveEvent']);
});
