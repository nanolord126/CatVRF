<?php declare(strict_types=1);

use App\Domains\Communication\Http\Controllers\CommunicationController;
use Illuminate\Support\Facades\Route;

/**
 * Communication API Routes v1
 * Production 2026.04.16
 */

// ===== AUTHENTICATED ENDPOINTS (Auth) =====
Route::middleware(['api', 'auth:sanctum', 'tenant', 'throttle:60,1'])->prefix('api/v1/communication')->group(function () {
    // List communication channels
    Route::get('channels', [CommunicationController::class, 'index'])
        ->name('communication.channels.index');
    
    // Create channel
    Route::post('channels', [CommunicationController::class, 'store'])
        ->name('communication.channels.store')
        ->middleware('throttle:30,1');
    
    // Show channel
    Route::get('channels/{channel}', [CommunicationController::class, 'show'])
        ->name('communication.channels.show');
    
    // Update channel
    Route::put('channels/{channel}', [CommunicationController::class, 'update'])
        ->name('communication.channels.update')
        ->middleware('throttle:30,1');
    
    // Disable channel
    Route::delete('channels/{channel}', [CommunicationController::class, 'destroy'])
        ->name('communication.channels.destroy')
        ->middleware('throttle:20,1');
    
    // Send message
    Route::post('messages', [CommunicationController::class, 'sendMessage'])
        ->name('communication.messages.send')
        ->middleware('throttle:30,1');
    
    // Inbox
    Route::get('inbox', [CommunicationController::class, 'inbox'])
        ->name('communication.inbox');
    
    // Mark message as read
    Route::post('messages/{message}/read', [CommunicationController::class, 'markRead'])
        ->name('communication.messages.read')
        ->middleware('throttle:30,1');
});
