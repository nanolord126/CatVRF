<?php declare(strict_types=1);

use App\Domains\Insurance\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

/**
 * Insurance API Routes v1
 * Production 2026.04.16
 */

// ===== AUTHENTICATED ENDPOINTS (Auth) =====
Route::middleware(['api', 'auth:sanctum', 'tenant', 'throttle:60,1'])->prefix('api/v1/insurance')->group(function () {
    // Create insurance order
    Route::post('orders', [OrderController::class, 'create'])
        ->name('insurance.orders.store')
        ->middleware('throttle:30,1');
    
    // Get delivery estimate
    Route::post('delivery-estimate', [OrderController::class, 'getDeliveryEstimate'])
        ->name('insurance.delivery-estimate')
        ->middleware('throttle:30,1');
});
