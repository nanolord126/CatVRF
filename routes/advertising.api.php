<?php

declare(strict_types=1);

use App\Domains\Advertising\Http\Controllers\AdCampaignController;
use App\Domains\Advertising\Http\Controllers\AdShortController;
use App\Domains\Advertising\Http\Controllers\AuctionController;
use App\Domains\Advertising\Http\Controllers\RTBController;
use Illuminate\Support\Facades\Route;

/**
 * Advertising & Marketing API Routes v1
 * Production 2026.03.24
 * Updated 2026.04.28 - Ad Exchange (Shorts, Auctions, RTB)
 */

// ===== PUBLIC ENDPOINTS (No Auth) =====
Route::middleware(['api', 'throttle:60,1'])->prefix('api/v1/advertising')->group(function () {
    // List campaigns (with filters)
    Route::get('campaigns', [AdCampaignController::class, 'index'])
        ->name('advertising.campaigns.index');

    // Get campaign details
    Route::get('campaigns/{campaign}', [AdCampaignController::class, 'show'])
        ->name('advertising.campaigns.show');

    // List short ads (public)
    Route::get('shorts', [AdShortController::class, 'index'])
        ->name('advertising.shorts.index');

    // Get short ad details
    Route::get('shorts/{uuid}', [AdShortController::class, 'show'])
        ->name('advertising.shorts.show');

    // List auctions (public)
    Route::get('auctions', [AuctionController::class, 'index'])
        ->name('advertising.auctions.index');

    // Get auction details
    Route::get('auctions/{uuid}', [AuctionController::class, 'show'])
        ->name('advertising.auctions.show');

    // Get auction bids (public)
    Route::get('auctions/{uuid}/bids', [AuctionController::class, 'bids'])
        ->name('advertising.auctions.bids');
});

// ===== RTB ENDPOINTS (OpenRTB 2.6 Compliant) =====
Route::middleware(['api', 'throttle:1000,1'])->prefix('api/v1/rtb')->group(function () {
    // Bid request (OpenRTB 2.6)
    Route::post('bid', [RTBController::class, 'bid'])
        ->name('rtb.bid');

    // Win notification
    Route::post('win', [RTBController::class, 'win'])
        ->name('rtb.win');

    // Impression tracking
    Route::get('impression', [RTBController::class, 'impression'])
        ->name('rtb.impression');

    // Click tracking
    Route::post('click', [RTBController::class, 'click'])
        ->name('rtb.click');
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

    // Create short ad
    Route::post('shorts', [AdShortController::class, 'store'])
        ->name('advertising.shorts.store')
        ->middleware('throttle:30,1');

    // Activate short ad
    Route::post('shorts/{uuid}/activate', [AdShortController::class, 'activate'])
        ->name('advertising.shorts.activate');

    // Pause short ad
    Route::post('shorts/{uuid}/pause', [AdShortController::class, 'pause'])
        ->name('advertising.shorts.pause');

    // Place bid on auction
    Route::post('auctions/{uuid}/bid', [AuctionController::class, 'bid'])
        ->name('advertising.auctions.bid')
        ->middleware('throttle:100,1');

    // Close auction
    Route::post('auctions/{uuid}/close', [AuctionController::class, 'close'])
        ->name('advertising.auctions.close');
});
