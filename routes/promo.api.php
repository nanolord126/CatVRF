<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Promo\PromoController;

/**
 * Promo & Campaigns API Routes v1
 * Production 2026.03.24
 */

// ===== AUTHENTICATED ENDPOINTS (Auth) =====
Route::middleware(['api', 'auth:sanctum', 'tenant', 'throttle:60,1'])->prefix('api/v1/promo')->group(function () {
    // Apply promo code
    Route::post('/apply', [PromoController::class, 'apply'])
        ->name('api.promo.apply')
        ->middleware('throttle:50,1');
    
    // Validate promo code (preview discount)
    Route::get('/{code}/validate', [PromoController::class, 'validate'])
        ->name('api.promo.validate');
    
    // Get active campaigns for user
    Route::get('/campaigns/active', [PromoController::class, 'getActiveCampaigns'])
        ->name('api.promo.campaigns.active');
    
    // Get user's promo usage history
    Route::get('/history', [PromoController::class, 'getHistory'])
        ->name('api.promo.history');
});
