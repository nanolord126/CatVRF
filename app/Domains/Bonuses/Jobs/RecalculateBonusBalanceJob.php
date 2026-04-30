<?php

declare(strict_types=1);

namespace App\Domains\Bonuses\Jobs;

use App\Domains\Bonuses\Models\BonusWallet;
use App\Domains\Bonuses\Models\BonusTransaction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Psr\Log\LoggerInterface;

/**
 * RecalculateBonusBalanceJob - Queue job for recalculating bonus balances
 * 
 * Recalculates bonus wallet balances from transaction history.
 * Used for data integrity checks and corrections.
 */
final readonly class RecalculateBonusBalanceJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use SerializesModels;

    public int $tries = 2;
    public int $timeout = 300;

    public function __construct(
        private readonly string $walletId,
        private readonly ?string $correlationId = null,
    ) {}

    public function handle(LoggerInterface $logger): void
    {
        try {
            $wallet = BonusWallet::findOrFail($this->walletId);

            // Calculate actual balances from transactions
            $transactions = BonusTransaction::where('wallet_id', $this->walletId)
                ->whereIn('status', ['pending', 'credited', 'spent'])
                ->get();

            $pendingBalance = 0;
            $availableBalance = 0;

            foreach ($transactions as $tx) {
                if ($tx->status === 'pending' && $tx->type === 'award') {
                    $pendingBalance += $tx->amount;
                } elseif ($tx->status === 'credited' && $tx->type === 'award') {
                    $availableBalance += $tx->amount;
                } elseif ($tx->status === 'spent' && $tx->type === 'spend') {
                    $availableBalance -= $tx->amount;
                }
            }

            // Update wallet if balances differ
            if ($wallet->pending_balance !== $pendingBalance || 
                $wallet->available_balance !== $availableBalance) {
                
                DB::transaction(function () use ($wallet, $pendingBalance, $availableBalance) {
                    $wallet->update([
                        'pending_balance' => $pendingBalance,
                        'available_balance' => max(0, $availableBalance),
                        'total_earned' => $pendingBalance + max(0, $availableBalance),
                    ]);
                });

                $logger->info('Bonus balance recalculated and updated', [
                    'wallet_id' => $this->walletId,
                    'old_pending' => $wallet->pending_balance,
                    'new_pending' => $pendingBalance,
                    'old_available' => $wallet->available_balance,
                    'new_available' => $availableBalance,
                    'correlation_id' => $this->correlationId,
                ]);
            } else {
                $logger->info('Bonus balance verified - no changes needed', [
                    'wallet_id' => $this->walletId,
                    'pending_balance' => $pendingBalance,
                    'available_balance' => $availableBalance,
                ]);
            }
        } catch (\Exception $e) {
            $logger->error('Bonus balance recalculation failed', [
                'wallet_id' => $this->walletId,
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);

            throw $e;
        }
    }
}
