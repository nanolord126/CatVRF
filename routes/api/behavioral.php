<?php

declare(strict_types=1);

use App\Http\Controllers\Api\BehavioralBiometricsController;
use Illuminate\Support\Facades\Route;

/**
 * Behavioral Biometrics API Routes
 *
 * CatVRF 2026 Enterprise Security
 */

Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    // Collect behavioral signals
    Route::post('/behavioral/collect', [BehavioralBiometricsController::class, 'collect'])
        ->name('behavioral.collect');

    // Get baseline status
    Route::get('/behavioral/baseline', [BehavioralBiometricsController::class, 'getBaseline'])
        ->name('behavioral.baseline');

    // Get session score
    Route::get('/behavioral/session-score', [BehavioralBiometricsController::class, 'getSessionScore'])
        ->name('behavioral.session-score');

    // Reset profile (privacy/right to deletion)
    Route::delete('/behavioral/profile', [BehavioralBiometricsController::class, 'resetProfile'])
        ->name('behavioral.reset-profile');

    // Consent management
    Route::put('/behavioral/consent', [BehavioralBiometricsController::class, 'updateConsent'])
        ->name('behavioral.update-consent');

    Route::get('/behavioral/consent', [BehavioralBiometricsController::class, 'getConsent'])
        ->name('behavioral.get-consent');
});
