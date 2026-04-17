<?php declare(strict_types=1);

use App\Domains\EventPlanning\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

/**
 * Event Planning API Routes v1
 * Production 2026.03.24
 */

// ===== AUTHENTICATED ENDPOINTS (Auth) =====
Route::middleware(['api', 'auth:sanctum', 'tenant', 'throttle:60,1'])->prefix('api/v1/event-planning')->group(function () {
    // Create event plan order
    Route::post('orders', [OrderController::class, 'create'])
        ->name('event-planning.orders.store')
        ->middleware('throttle:30,1');
    
    // Get delivery estimate
    Route::post('delivery-estimate', [OrderController::class, 'getDeliveryEstimate'])
        ->name('event-planning.delivery-estimate')
        ->middleware('throttle:30,1');
});
