<?php declare(strict_types=1);

use App\Domains\Collectibles\Controllers\CollectibleItemController;
use Illuminate\Support\Facades\Route;

/**
 * Collectibles API Routes v1
 * Production 2026.04.16
 */

// ===== PUBLIC ENDPOINTS (No Auth) =====
Route::middleware(['api', 'throttle:60,1'])->prefix('api/v1/collectibles')->group(function () {
    // List collectible items (with filters)
    Route::get('items', [CollectibleItemController::class, 'index'])
        ->name('collectibles.items.index');
    
    // Get item details
    Route::get('items/{item}', [CollectibleItemController::class, 'show'])
        ->name('collectibles.items.show');
});

// ===== AUTHENTICATED ENDPOINTS (Auth) =====
Route::middleware(['api', 'auth:sanctum', 'tenant', 'throttle:60,1'])->prefix('api/v1/collectibles')->group(function () {
    // Create collectible item
    Route::post('items', [CollectibleItemController::class, 'store'])
        ->name('collectibles.items.store')
        ->middleware('throttle:20,1');
    
    // Update item
    Route::put('items/{item}', [CollectibleItemController::class, 'update'])
        ->name('collectibles.items.update')
        ->middleware('throttle:30,1');
    
    // Delete item
    Route::delete('items/{item}', [CollectibleItemController::class, 'destroy'])
        ->name('collectibles.items.destroy')
        ->middleware('throttle:20,1');
});
