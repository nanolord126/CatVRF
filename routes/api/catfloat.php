<?php

declare(strict_types=1);

use App\Domains\Bonuses\Infrastructure\Http\Controllers\CatFloatController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| CatFloat Rewards API Routes
|--------------------------------------------------------------------------
|
| Endpoints for CatFloat Rewards system with 15-day Smart Hold.
|
*/

Route::middleware(['auth:sanctum', 'tenant'])->prefix('catfloat')->group(function () {
    // Bonus Operations
    Route::post('/award', [CatFloatController::class, 'awardBonus']);
    Route::get('/balance', [CatFloatController::class, 'getLockedBalance']);
    Route::get('/batches', [CatFloatController::class, 'getBatches']);
    Route::post('/unlock', [CatFloatController::class, 'unlockBonus']);

    // Activity & Streak
    Route::post('/activity', [CatFloatController::class, 'logActivity']);
    Route::post('/streak', [CatFloatController::class, 'processStreak']);
    Route::get('/streak/info', [CatFloatController::class, 'getStreakInfo']);
    Route::post('/yield/claim', [CatFloatController::class, 'claimYield']);

    // Float Yield
    Route::post('/yield/calculate', [CatFloatController::class, 'calculateYield']);
    Route::get('/yield/history', [CatFloatController::class, 'getYieldHistory']);

    // Marketplace
    Route::post('/marketplace/sell', [CatFloatController::class, 'sellLockedBonus']);
    Route::get('/marketplace/info', [CatFloatController::class, 'getMarketplaceInfo']);

    // Dashboard
    Route::get('/dashboard', [CatFloatController::class, 'getDashboard']);
});
