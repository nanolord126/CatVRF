<?php declare(strict_types=1);

use App\Domains\PartySupplies\Controllers\PartyOrderController;
use Illuminate\Support\Facades\Route;

/**
 * Party Supplies API Routes v1
 * Production 2026.04.16
 */

// ===== PUBLIC ENDPOINTS (No Auth) =====
Route::middleware(['api', 'throttle:60,1'])->prefix('api/v1/party-supplies')->group(function () {
    // List party orders (with filters)
    Route::get('orders', [PartyOrderController::class, 'index'])
        ->name('party-supplies.orders.index');
    
    // Get order details
    Route::get('orders/{order}', [PartyOrderController::class, 'show'])
        ->name('party-supplies.orders.show');
});

// ===== AUTHENTICATED ENDPOINTS (Auth) =====
Route::middleware(['api', 'auth:sanctum', 'tenant', 'throttle:60,1'])->prefix('api/v1/party-supplies')->group(function () {
    // Create party order
    Route::post('orders', [PartyOrderController::class, 'store'])
        ->name('party-supplies.orders.store')
        ->middleware('throttle:20,1');
    
    // Update order
    Route::put('orders/{order}', [PartyOrderController::class, 'update'])
        ->name('party-supplies.orders.update')
        ->middleware('throttle:30,1');
    
    // Cancel order
    Route::delete('orders/{order}', [PartyOrderController::class, 'destroy'])
        ->name('party-supplies.orders.destroy')
        ->middleware('throttle:20,1');
});
