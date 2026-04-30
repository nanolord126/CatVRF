<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Loyalty\Presentation\Http\Controllers\LoyaltyController;

Route::middleware(['api', 'auth:sanctum'])->group(function () {
    Route::prefix('loyalty')->group(function () {
        Route::prefix('guests')->group(function () {
            Route::post('enroll', [LoyaltyController::class, 'enrollGuest'])
                ->name('loyalty.guests.enroll');

            Route::get('{guestId}/programs/{programId}/balance', [LoyaltyController::class, 'getProfileBalance'])
                ->name('loyalty.guests.balance');

            Route::get('{guestId}/programs/{programId}', [LoyaltyController::class, 'getProfile'])
                ->name('loyalty.guests.profile');
        });

        Route::prefix('programs')->group(function () {
            Route::post('{programId}/calculate-points', [LoyaltyController::class, 'calculatePoints'])
                ->name('loyalty.programs.calculate-points');

            Route::post('{programId}/process-order', [LoyaltyController::class, 'processOrderLoyalty'])
                ->name('loyalty.programs.process-order');

            Route::post('{programId}/birthday-bonus', [LoyaltyController::class, 'giveBirthdayBonus'])
                ->name('loyalty.programs.birthday-bonus');

            Route::get('{programId}/rewards', [LoyaltyController::class, 'getRewards'])
                ->name('loyalty.programs.rewards');
        });

        Route::post('rewards/redeem', [LoyaltyController::class, 'redeemReward'])
            ->name('loyalty.rewards.redeem');
    });
});
