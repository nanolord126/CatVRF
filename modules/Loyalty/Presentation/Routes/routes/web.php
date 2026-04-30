<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Loyalty\Livewire\KDSLoyaltyDisplay;
use Modules\Loyalty\Livewire\LoyaltyRewardSelector;
use Modules\Loyalty\Livewire\OrderLoyaltyDisplay;

Route::middleware(['web'])->group(function () {
    // Livewire component routes are auto-registered
    // These are just example API routes if needed

    Route::prefix('api/loyalty')->group(function () {
        // Add API routes if needed
    });
});
