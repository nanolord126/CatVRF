<?php declare(strict_types=1);

use App\Domains\CleaningServices\Controllers\CleaningOrderController;
use Illuminate\Support\Facades\Route;

/**
 * Cleaning Services API Routes v1
 * Production 2026.04.16
 */

// ===== PUBLIC ENDPOINTS (No Auth) =====
Route::middleware(['api', 'throttle:60,1'])->prefix('api/v1/cleaning-services')->group(function () {
    // List cleaning orders (with filters)
    Route::get('orders', [CleaningOrderController::class, 'index'])
        ->name('cleaning-services.orders.index');
    
    // Get order details
    Route::get('orders/{order}', [CleaningOrderController::class, 'show'])
        ->name('cleaning-services.orders.show');
});

// ===== AUTHENTICATED ENDPOINTS (Auth) =====
Route::middleware(['api', 'auth:sanctum', 'tenant', 'throttle:60,1'])->prefix('api/v1/cleaning-services')->group(function () {
    // Create cleaning order
    Route::post('orders', [CleaningOrderController::class, 'store'])
        ->name('cleaning-services.orders.store')
        ->middleware('throttle:20,1');
    
    // Update order
    Route::put('orders/{order}', [CleaningOrderController::class, 'update'])
        ->name('cleaning-services.orders.update')
        ->middleware('throttle:30,1');
    
    // Cancel order
    Route::delete('orders/{order}', [CleaningOrderController::class, 'destroy'])
        ->name('cleaning-services.orders.destroy')
        ->middleware('throttle:20,1');
});
