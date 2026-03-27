<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Modules\Wallet\Http\Controllers\WalletController;

/**
 * Wallet Module Routes - Production 2026.
 */
Route::middleware(['auth:sanctum', 'tenant'])
    ->prefix('api/wallet')
    ->group(function () {
        Route::get('/balance', [WalletController::class, 'balance'])->name('wallet.balance');
        Route::post('/deposit', [WalletController::class, 'deposit'])->name('wallet.deposit');
        Route::post('/withdraw', [WalletController::class, 'withdraw'])->name('wallet.withdraw');
        Route::get('/transactions', [WalletController::class, 'index'])->name('wallet.transactions');
        Route::get('/transactions/{transaction}', [WalletController::class, 'show'])->name('wallet.show');
        Route::get('/history', [WalletController::class, 'history'])->name('wallet.history');
        Route::get('/statement', [WalletController::class, 'statement'])->name('wallet.statement');
    });
