<?php declare(strict_types=1);

use App\Domains\HobbyAndCraft\Controllers\CraftItemController;
use Illuminate\Support\Facades\Route;

/**
 * Hobby And Craft API Routes v1
 * Production 2026.04.16
 */

// ===== PUBLIC ENDPOINTS (No Auth) =====
Route::middleware(['api', 'throttle:60,1'])->prefix('api/v1/hobby-and-craft')->group(function () {
    // List craft items (with filters)
    Route::get('items', [CraftItemController::class, 'index'])
        ->name('hobby-and-craft.items.index');
    
    // Get item details
    Route::get('items/{item}', [CraftItemController::class, 'show'])
        ->name('hobby-and-craft.items.show');
});

// ===== AUTHENTICATED ENDPOINTS (Auth) =====
Route::middleware(['api', 'auth:sanctum', 'tenant', 'throttle:60,1'])->prefix('api/v1/hobby-and-craft')->group(function () {
    // Create craft item
    Route::post('items', [CraftItemController::class, 'store'])
        ->name('hobby-and-craft.items.store')
        ->middleware('throttle:20,1');
    
    // Update item
    Route::put('items/{item}', [CraftItemController::class, 'update'])
        ->name('hobby-and-craft.items.update')
        ->middleware('throttle:30,1');
    
    // Delete item
    Route::delete('items/{item}', [CraftItemController::class, 'destroy'])
        ->name('hobby-and-craft.items.destroy')
        ->middleware('throttle:20,1');
});
