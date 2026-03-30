<?php declare(strict_types=1);

namespace Modules\Wallet\Routes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class api extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    Route::get('/balance', [WalletController::class, 'balance'])->name('wallet.balance');
            Route::post('/deposit', [WalletController::class, 'deposit'])->name('wallet.deposit');
            Route::post('/withdraw', [WalletController::class, 'withdraw'])->name('wallet.withdraw');
            Route::get('/transactions', [WalletController::class, 'index'])->name('wallet.transactions');
            Route::get('/transactions/{transaction}', [WalletController::class, 'show'])->name('wallet.show');
            Route::get('/history', [WalletController::class, 'history'])->name('wallet.history');
            Route::get('/statement', [WalletController::class, 'statement'])->name('wallet.statement');
}
