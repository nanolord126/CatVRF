<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\VoiceBiometricsController;

Route::middleware('auth:sanctum')->group(function () {
    // Voice Biometrics endpoints
    Route::prefix('voice-biometrics')->group(function () {
        Route::post('/enroll', [VoiceBiometricsController::class, 'enroll'])
            ->name('voice-biometrics.enroll');

        Route::post('/verify', [VoiceBiometricsController::class, 'verify'])
            ->name('voice-biometrics.verify');

        Route::get('/status', [VoiceBiometricsController::class, 'status'])
            ->name('voice-biometrics.status');

        Route::delete('/', [VoiceBiometricsController::class, 'delete'])
            ->name('voice-biometrics.delete');
    });
});
