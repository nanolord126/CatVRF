<?php declare(strict_types=1);

use App\Domains\Marketplace\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

/**
 * Marketplace API Routes v1
 * Production 2026.04.16
 */

// ===== AUTHENTICATED ENDPOINTS (Auth) =====
Route::middleware(['api', 'auth:sanctum', 'tenant', 'throttle:60,1'])->prefix('api/v1/marketplace')->group(function () {
    // Create marketplace order
    Route::post('orders', [OrderController::class, 'create'])
        ->name('marketplace.orders.store')
        ->middleware('throttle:30,1');
    
    // Get delivery estimate
    Route::post('delivery-estimate', [OrderController::class, 'getDeliveryEstimate'])
        ->name('marketplace.delivery-estimate')
        ->middleware('throttle:30,1');
});
