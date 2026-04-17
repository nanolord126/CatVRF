<?php declare(strict_types=1);

use App\Domains\HouseholdGoods\Controllers\HouseholdProductController;
use Illuminate\Support\Facades\Route;

/**
 * Household Goods API Routes v1
 * Production 2026.04.16
 */

// ===== PUBLIC ENDPOINTS (No Auth) =====
Route::middleware(['api', 'throttle:60,1'])->prefix('api/v1/household-goods')->group(function () {
    // List household products (with filters)
    Route::get('products', [HouseholdProductController::class, 'index'])
        ->name('household-goods.products.index');
    
    // Get product details
    Route::get('products/{product}', [HouseholdProductController::class, 'show'])
        ->name('household-goods.products.show');
});

// ===== AUTHENTICATED ENDPOINTS (Auth) =====
Route::middleware(['api', 'auth:sanctum', 'tenant', 'throttle:60,1'])->prefix('api/v1/household-goods')->group(function () {
    // Create household product
    Route::post('products', [HouseholdProductController::class, 'store'])
        ->name('household-goods.products.store')
        ->middleware('throttle:20,1');
    
    // Update product
    Route::put('products/{product}', [HouseholdProductController::class, 'update'])
        ->name('household-goods.products.update')
        ->middleware('throttle:30,1');
    
    // Delete product
    Route::delete('products/{product}', [HouseholdProductController::class, 'destroy'])
        ->name('household-goods.products.destroy')
        ->middleware('throttle:20,1');
});
