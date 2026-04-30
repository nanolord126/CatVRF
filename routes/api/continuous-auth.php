<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ContinuousAuthController;

Route::middleware(['auth:sanctum', 'throttle:60,1'])->prefix('v1/continuous-auth')->group(function () {
    // Get current session risk score
    Route::get('/session/risk', [ContinuousAuthController::class, 'getSessionRisk']);

    // Get behavioral data points (for debugging)
    Route::get('/session/behavioral-data', [ContinuousAuthController::class, 'getBehavioralData']);

    // Trigger manual challenge (for testing)
    Route::post('/session/challenge', [ContinuousAuthController::class, 'triggerChallenge']);

    // Submit challenge response
    Route::post('/session/challenge/verify', [ContinuousAuthController::class, 'verifyChallenge']);

    // Get trust score
    Route::get('/session/trust', [ContinuousAuthController::class, 'getTrustScore']);

    // Start monitoring for current session
    Route::post('/session/start', [ContinuousAuthController::class, 'startMonitoring']);

    // Stop monitoring for current session
    Route::post('/session/stop', [ContinuousAuthController::class, 'stopMonitoring']);
});
