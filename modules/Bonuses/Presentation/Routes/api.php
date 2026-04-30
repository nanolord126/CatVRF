<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Bonuses\Presentation\Http\Controllers\BonusController;
use Modules\Bonuses\Presentation\Http\Controllers\LoyaltyController;
use Modules\Bonuses\Presentation\Http\Controllers\BonusProgramController;

/*
|--------------------------------------------------------------------------
| Bonuses Vertical API Routes
|--------------------------------------------------------------------------
|
| API endpoints for bonus operations, loyalty management, and bonus programs.
| All routes require authentication and proper authorization.
|
*/

Route::middleware(['auth:sanctum'])->prefix('api/v1/bonuses')->group(function () {
    /*
    |--------------------------------------------------------------------------
    | Bonus Operations
    |--------------------------------------------------------------------------
    */
    Route::prefix('bonuses')->group(function () {
        // Award a bonus to a user
        Route::post('/', [BonusController::class, 'award'])
            ->name('bonuses.award')
            ->middleware(['can:award bonuses']);

        // Consume bonuses
        Route::post('/consume', [BonusController::class, 'consume'])
            ->name('bonuses.consume')
            ->middleware(['can:consume bonuses']);

        // Get user's available balance
        Route::get('/balance', [BonusController::class, 'getBalance'])
            ->name('bonuses.balance');

        // Get balance breakdown by type
        Route::get('/balance/by-type', [BonusController::class, 'getBalanceByType'])
            ->name('bonuses.balance.by-type');

        // Get user's bonuses with optional filtering
        Route::get('/', [BonusController::class, 'index'])
            ->name('bonuses.index');

        // Get specific bonus details
        Route::get('/{id}', [BonusController::class, 'show'])
            ->name('bonuses.show');

        // Freeze a bonus (admin only)
        Route::post('/{id}/freeze', [BonusController::class, 'freeze'])
            ->name('bonuses.freeze')
            ->middleware(['can:manage bonuses']);

        // Unfreeze a bonus (admin only)
        Route::post('/{id}/unfreeze', [BonusController::class, 'unfreeze'])
            ->name('bonuses.unfreeze')
            ->middleware(['can:manage bonuses']);

        // Cancel a bonus (admin only)
        Route::post('/{id}/cancel', [BonusController::class, 'cancel'])
            ->name('bonuses.cancel')
            ->middleware(['can:manage bonuses']);
    });

    /*
    |--------------------------------------------------------------------------
    | Loyalty Operations
    |--------------------------------------------------------------------------
    */
    Route::prefix('loyalty')->group(function () {
        // Get user's loyalty status
        Route::get('/status', [LoyaltyController::class, 'getStatus'])
            ->name('loyalty.status');

        // Add loyalty points
        Route::post('/points', [LoyaltyController::class, 'addPoints'])
            ->name('loyalty.points.add')
            ->middleware(['can:manage loyalty']);

        // Get loyalty tier trajectory
        Route::get('/trajectory', [LoyaltyController::class, 'getTrajectory'])
            ->name('loyalty.trajectory');

        // Get available benefits
        Route::get('/benefits', [LoyaltyController::class, 'getBenefits'])
            ->name('loyalty.benefits');

        // Get points needed for next tier
        Route::get('/points-to-next', [LoyaltyController::class, 'getPointsToNext'])
            ->name('loyalty.points-to-next');
    });

    /*
    |--------------------------------------------------------------------------
    | Bonus Program Operations (Admin)
    |--------------------------------------------------------------------------
    */
    Route::prefix('programs')->middleware(['can:manage bonus programs'])->group(function () {
        // List all programs
        Route::get('/', [BonusProgramController::class, 'index'])
            ->name('bonuses.programs.index');

        // Create a new program
        Route::post('/', [BonusProgramController::class, 'store'])
            ->name('bonuses.programs.store');

        // Get specific program
        Route::get('/{id}', [BonusProgramController::class, 'show'])
            ->name('bonuses.programs.show');

        // Update a program
        Route::put('/{id}', [BonusProgramController::class, 'update'])
            ->name('bonuses.programs.update');

        // Delete a program
        Route::delete('/{id}', [BonusProgramController::class, 'destroy'])
            ->name('bonuses.programs.destroy');

        // Activate a program
        Route::post('/{id}/activate', [BonusProgramController::class, 'activate'])
            ->name('bonuses.programs.activate');

        // Deactivate a program
        Route::post('/{id}/deactivate', [BonusProgramController::class, 'deactivate'])
            ->name('bonuses.programs.deactivate');
    });

    /*
    |--------------------------------------------------------------------------
    | Admin Operations
    |--------------------------------------------------------------------------
    */
    Route::prefix('admin')->middleware(['can:administer bonuses'])->group(function () {
        // Get expiring bonuses
        Route::get('/expiring', [BonusController::class, 'getExpiring'])
            ->name('bonuses.admin.expiring');

        // Get frozen bonuses
        Route::get('/frozen', [BonusController::class, 'getFrozen'])
            ->name('bonuses.admin.frozen');

        // Search bonuses
        Route::post('/search', [BonusController::class, 'search'])
            ->name('bonuses.admin.search');

        // Get analytics
        Route::get('/analytics', [BonusController::class, 'getAnalytics'])
            ->name('bonuses.admin.analytics');

        // Trigger bonus expiration (manual)
        Route::post('/expire', [BonusController::class, 'expireBonuses'])
            ->name('bonuses.admin.expire');

        // Get bonuses by correlation ID
        Route::get('/by-correlation/{correlationId}', [BonusController::class, 'getByCorrelationId'])
            ->name('bonuses.admin.by-correlation');
    });
});
