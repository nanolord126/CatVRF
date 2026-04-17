<?php declare(strict_types=1);

use App\Domains\SportsNutrition\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

/**
 * Sports Nutrition API Routes v1
 * Production 2026.04.16
 */

// ===== AUTHENTICATED ENDPOINTS (Auth) =====
Route::middleware(['api', 'auth:sanctum', 'tenant', 'throttle:60,1'])->prefix('api/v1/sports-nutrition')->group(function () {
    // Create sports nutrition order
    Route::post('orders', [OrderController::class, 'create'])
        ->name('sports-nutrition.orders.store')
        ->middleware('throttle:30,1');
    
    // Get delivery estimate
    Route::post('delivery-estimate', [OrderController::class, 'getDeliveryEstimate'])
        ->name('sports-nutrition.delivery-estimate')
        ->middleware('throttle:30,1');
});
