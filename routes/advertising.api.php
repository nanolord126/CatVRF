<?php declare(strict_types=1);

use App\Domains\Advertising\Http\Controllers\AdCampaignController;
use Illuminate\Support\Facades\Route;

/**
 * Advertising & Marketing API Routes v1
 * Production 2026.03.24
 */

// ===== PUBLIC ENDPOINTS (No Auth) =====
Route::middleware(['api', 'throttle:60,1'])->prefix('api/v1/advertising')->group(function () {
    // List campaigns (with filters)
    Route::get('campaigns', [AdCampaignController::class, 'index'])
        ->name('advertising.campaigns.index');
    
    // Get campaign details
    Route::get('campaigns/{campaign}', [AdCampaignController::class, 'show'])
        ->name('advertising.campaigns.show');
});

// ===== AUTHENTICATED ENDPOINTS (Auth) =====
Route::middleware(['api', 'auth:sanctum', 'tenant', 'throttle:60,1'])->prefix('api/v1/advertising')->group(function () {
    // Create campaign
    Route::post('campaigns', [AdCampaignController::class, 'store'])
        ->name('advertising.campaigns.store')
        ->middleware('throttle:30,1');
    
    // Update campaign
    Route::put('campaigns/{campaign}', [AdCampaignController::class, 'update'])
        ->name('advertising.campaigns.update')
        ->middleware('throttle:30,1');
    
    // Delete campaign
    Route::delete('campaigns/{campaign}', [AdCampaignController::class, 'destroy'])
        ->name('advertising.campaigns.destroy')
        ->middleware('throttle:20,1');
});
