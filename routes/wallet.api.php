<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Wallet\WalletController;

/**
 * Wallet & Balance API Routes v1
 * Production 2026.03.24
 */

// ===== AUTHENTICATED ENDPOINTS (Auth) =====
Route::middleware(['api', 'auth:sanctum', 'tenant', 'throttle:60,1'])->prefix('api/v1/wallet')->group(function () {
    // Get wallet balance
    Route::get('/', [WalletController::class, 'show'])
        ->name('api.wallet.show');
    
    // Get transaction history
    Route::get('/transactions', [WalletController::class, 'getTransactions'])
        ->name('api.wallet.transactions');
    
    // Get wallet statistics
    Route::get('/stats', [WalletController::class, 'getStats'])
        ->name('api.wallet.stats');
});
