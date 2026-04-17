<?php declare(strict_types=1);

use App\Domains\VeganProducts\Controllers\VeganModelsController;
use Illuminate\Support\Facades\Route;

/**
 * Vegan Products API Routes v1
 * Production 2026.04.16
 */

// ===== PUBLIC ENDPOINTS (No Auth) =====
Route::middleware(['api', 'throttle:60,1'])->prefix('api/v1/vegan-products')->group(function () {
    // List vegan products (with filters)
    Route::get('products', [VeganModelsController::class, 'index'])
        ->name('vegan-products.products.index');
    
    // Get product details
    Route::get('products/{product}', [VeganModelsController::class, 'show'])
        ->name('vegan-products.products.show');
});

// ===== AUTHENTICATED ENDPOINTS (Auth) =====
Route::middleware(['api', 'auth:sanctum', 'tenant', 'throttle:60,1'])->prefix('api/v1/vegan-products')->group(function () {
    // Create vegan product
    Route::post('products', [VeganModelsController::class, 'store'])
        ->name('vegan-products.products.store')
        ->middleware('throttle:20,1');
    
    // Update product
    Route::put('products/{product}', [VeganModelsController::class, 'update'])
        ->name('vegan-products.products.update')
        ->middleware('throttle:30,1');
    
    // Delete product
    Route::delete('products/{product}', [VeganModelsController::class, 'destroy'])
        ->name('vegan-products.products.destroy')
        ->middleware('throttle:20,1');
});
