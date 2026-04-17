<?php declare(strict_types=1);

use App\Domains\Delivery\Http\Controllers\DeliveryController;
use Illuminate\Support\Facades\Route;

/**
 * Delivery API Routes v1
 * Production 2026.03.24
 */

// ===== PUBLIC ENDPOINTS (No Auth) =====
Route::middleware(['api', 'throttle:60,1'])->prefix('api/v1/delivery')->group(function () {
    // List deliveries (with filters)
    Route::get('deliveries', [DeliveryController::class, 'index'])
        ->name('delivery.deliveries.index');
    
    // Get delivery details
    Route::get('deliveries/{delivery}', [DeliveryController::class, 'show'])
        ->name('delivery.deliveries.show');
});

// ===== AUTHENTICATED ENDPOINTS (Auth) =====
Route::middleware(['api', 'auth:sanctum', 'tenant', 'throttle:60,1'])->prefix('api/v1/delivery')->group(function () {
    // Create delivery
    Route::post('deliveries', [DeliveryController::class, 'store'])
        ->name('delivery.deliveries.store')
        ->middleware('throttle:30,1');
    
    // Update delivery status
    Route::put('deliveries/{delivery}', [DeliveryController::class, 'update'])
        ->name('delivery.deliveries.update')
        ->middleware('throttle:30,1');
    
    // Cancel delivery
    Route::delete('deliveries/{delivery}', [DeliveryController::class, 'destroy'])
        ->name('delivery.deliveries.destroy')
        ->middleware('throttle:20,1');
});
