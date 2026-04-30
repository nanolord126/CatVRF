<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\VideoController;

Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
    // Video Room Routes
    Route::post('/video/rooms', [VideoController::class, 'createRoom']);
    Route::post('/video/rooms/{roomId}/start', [VideoController::class, 'startRoom']);
    Route::post('/video/rooms/{roomId}/end', [VideoController::class, 'endRoom']);
    Route::post('/video/rooms/{roomId}/join', [VideoController::class, 'joinRoom']);
    Route::get('/video/rooms/{roomId}', [VideoController::class, 'getRoom']);
    
    // Recording Consent Routes
    Route::post('/video/rooms/{roomId}/recording-consent', [VideoController::class, 'grantRecordingConsent']);
    
    // Recording Download Routes
    Route::get('/video/recordings/{recordingId}/download', [VideoController::class, 'downloadRecording']);
});
