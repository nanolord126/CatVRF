<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Referral\ReferralController;

/**
 * Referral Program API Routes v1
 * Production 2026.03.24
 */

// ===== PUBLIC ENDPOINTS (No Auth) =====
Route::middleware(['api', 'throttle:60,1'])->prefix('api/v1/referral')->group(function () {
    // Referral code validation
    Route::get('/{code}/validate', [ReferralController::class, 'validateCode'])
        ->name('api.referral.validate');
});

// ===== AUTHENTICATED ENDPOINTS (Auth) =====
Route::middleware(['api', 'auth:sanctum', 'tenant', 'throttle:60,1'])->prefix('api/v1/referral')->group(function () {
    // Generate referral link
    Route::post('/generate', [ReferralController::class, 'generate'])
        ->name('api.referral.generate')
        ->middleware('throttle:20,1');
    
    // Register with referral code
    Route::post('/register', [ReferralController::class, 'register'])
        ->name('api.referral.register')
        ->middleware('throttle:30,1');
    
    // Check referral qualification status
    Route::post('/{referral}/qualify', [ReferralController::class, 'qualify'])
        ->name('api.referral.qualify')
        ->middleware('throttle:20,1');
    
    // Get referral statistics
    Route::get('/stats', [ReferralController::class, 'stats'])
        ->name('api.referral.stats');
    
    // Get referral history
    Route::get('/history', [ReferralController::class, 'getHistory'])
        ->name('api.referral.history');
    
    // Get referral earnings
    Route::get('/earnings', [ReferralController::class, 'getEarnings'])
        ->name('api.referral.earnings');
});
