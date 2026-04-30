<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\BonusesController;

/*
|--------------------------------------------------------------------------
| Bonuses API Routes
|--------------------------------------------------------------------------
|
| API endpoints for bonus operations.
| All routes require authentication by default.
|
*/

Route::middleware(['auth:sanctum'])->prefix('api/bonuses')->group(function () {
    // Bonus balance
    Route::get('/balance/{userId}', [BonusesController::class, 'getBalance'])
        ->name('bonuses.balance');

    // Bonus history
    Route::get('/history/{userId}', [BonusesController::class, 'getHistory'])
        ->name('bonuses.history');

    // Calculate bonus for order
    Route::post('/calculate', [BonusesController::class, 'calculateBonus'])
        ->name('bonuses.calculate');

    // Get available rules
    Route::get('/rules/{ruleType}', [BonusesController::class, 'getRules'])
        ->name('bonuses.rules');

    // Award bonus (admin/internal use)
    Route::post('/award', [BonusesController::class, 'award'])
        ->middleware(['role:admin'])
        ->name('bonuses.award');

    // Spend bonus
    Route::post('/spend', [BonusesController::class, 'spend'])
        ->name('bonuses.spend');

    // Withdraw bonus (B2B only)
    Route::post('/withdraw', [BonusesController::class, 'withdraw'])
        ->middleware(['role:b2b'])
        ->name('bonuses.withdraw');
});

// Scheduled tasks (called by cron/scheduler)
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('api/bonuses/admin')->group(function () {
    Route::post('/unlock-holds', [BonusesController::class, 'unlockExpiredHolds'])
        ->name('bonuses.admin.unlock-holds');

    Route::post('/expire-old', [BonusesController::class, 'expireOldBonuses'])
        ->name('bonuses.admin.expire-old');

    Route::post('/recalculate/{walletId}', [BonusesController::class, 'recalculateBalance'])
        ->name('bonuses.admin.recalculate');
});
