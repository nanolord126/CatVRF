<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Video\Presentation\Http\Controllers\VideoRoomController;

Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::prefix('api/v1/video')->group(function () {
        Route::post('/rooms', [VideoRoomController::class, 'create']);
        Route::post('/rooms/{roomId}/start', [VideoRoomController::class, 'start']);
        Route::post('/rooms/{roomId}/end', [VideoRoomController::class, 'end']);
        Route::post('/rooms/{roomId}/join', [VideoRoomController::class, 'join']);
        Route::post('/rooms/{roomId}/leave/{userId}', [VideoRoomController::class, 'leave']);
        Route::post('/rooms/{roomId}/consent', [VideoRoomController::class, 'giveConsent']);
        Route::get('/rooms/{roomId}/access-token/{userId}', [VideoRoomController::class, 'getAccessToken']);
        Route::get('/rooms/{roomId}', [VideoRoomController::class, 'show']);
        Route::get('/rooms/by-host/{hostId}', [VideoRoomController::class, 'getByHost']);
    });
});
