<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\KYBController;

Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::post('/kyb/verify', [KYBController::class, 'startVerification'])
        ->middleware('VpnProtectionMiddleware');
    Route::get('/kyb/status/{tenantId}', [KYBController::class, 'getStatus']);
    Route::post('/kyb/rescreen/{tenantId}', [KYBController::class, 'reScreen'])
        ->middleware('VpnProtectionMiddleware');
});

Route::middleware(['auth:sanctum', 'role:admin,compliance'])->group(function () {
    Route::post('/kyb/{id}/approve', [KYBController::class, 'approve'])
        ->middleware('VpnProtectionMiddleware');
    Route::post('/kyb/{id}/reject', [KYBController::class, 'reject']);
    Route::get('/kyb/pending-review', [KYBController::class, 'pendingReview']);
});
