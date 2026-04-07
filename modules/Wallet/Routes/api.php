<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Wallet\Presentation\Http\Controllers\WalletController;

/*
|--------------------------------------------------------------------------
| Wallet API Routes (Clean Architecture — 9-layer)
|--------------------------------------------------------------------------
| Все операции делегируются UseCases.
| Rate-limiting и fraud-check внутри UseCases.
*/

Route::middleware(['api', 'auth:sanctum', 'tenant'])
    ->prefix('wallet')
    ->name('wallet.')
    ->group(function (): void {
        // GET /api/wallet/balance?tenant_id=X
        Route::get('/balance', [WalletController::class, 'balance'])
            ->name('balance');

        // POST /api/wallet/deposit  { amount, tenant_id, description? }
        Route::post('/deposit', [WalletController::class, 'deposit'])
            ->name('deposit');

        // POST /api/wallet/withdraw  { amount, tenant_id, description? }
        Route::post('/withdraw', [WalletController::class, 'withdraw'])
            ->name('withdraw');

        // POST /api/wallet/transfer  { to_user_id, amount, tenant_id, description? }
        Route::post('/transfer', [WalletController::class, 'transfer'])
            ->name('transfer');
    });

