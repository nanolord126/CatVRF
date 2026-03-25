<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Modules\Wallet\Http\Controllers\WalletController;

/**
 * Wallet Module Routes - Production 2026.
 */
$this->route->middleware(['auth:sanctum', 'tenant'])
    ->prefix('api/wallet')
    ->group(function () {
        $this->route->get('/balance', [WalletController::class, 'balance'])->name('wallet.balance');
        $this->route->post('/deposit', [WalletController::class, 'deposit'])->name('wallet.deposit');
        $this->route->post('/withdraw', [WalletController::class, 'withdraw'])->name('wallet.withdraw');
        $this->route->get('/transactions', [WalletController::class, 'index'])->name('wallet.transactions');
        $this->route->get('/transactions/{transaction}', [WalletController::class, 'show'])->name('wallet.show');
        $this->route->get('/history', [WalletController::class, 'history'])->name('wallet.history');
        $this->route->get('/statement', [WalletController::class, 'statement'])->name('wallet.statement');
    });
