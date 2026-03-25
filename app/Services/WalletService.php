<?php declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Wallet;
use App\Models\BalanceTransaction;
use Illuminate\Support\Str;

/**
 * Wallet & Balance Management Service
 * Production 2026 CANON
 *
 * Manages wallet operations: hold, release, credit, debit
 * - Atomic transactions ($this->db->transaction)
 * - Optimistic locking (lockForUpdate)
 * - Audit logging on every operation
 * - Correlation ID tracing
 *
 * @author CatVRF Team
 * @version 2026.03.24
 */
final class WalletService
{
    /**
     * Hold (reserve) funds
     *
     * @param int $walletId Wallet ID
     * @param int $amount Amount in kopeks
     * @param string $reason Operation reason
     * @param string $correlationId Tracing ID
     * @return bool
     * @throws \Exception
     */
    public function holdAmount(int $walletId, int $amount, string $reason, string $correlationId): bool
    {
        return $this->db->transaction(function () use ($walletId, $amount, $reason, $correlationId): bool {
            $wallet = Wallet::lockForUpdate()->findOrFail($walletId);

            // Check balance + hold_stock
            $availableBalance = $wallet->current_balance - $wallet->hold_amount;
            if ($availableBalance < $amount) {
                $this->log->channel('audit')->warning('Insufficient funds for hold', [
                    'correlation_id' => $correlationId,
                    'wallet_id' => $walletId,
                    'requested' => $amount,
                    'available' => $availableBalance,
                ]);
                throw new \Exception('Insufficient funds');
            }

            // Update hold_amount
            $wallet->update([
                'hold_amount' => $wallet->hold_amount + $amount,
                'updated_at' => now(),
            ]);

            // Log transaction
            BalanceTransaction::create([
                'wallet_id' => $walletId,
                'type' => 'hold',
                'amount' => $amount,
                'status' => 'pending',
                'reason' => $reason,
                'correlation_id' => $correlationId,
            ]);

            $this->log->channel('audit')->info('Hold amount', [
                'correlation_id' => $correlationId,
                'wallet_id' => $walletId,
                'amount' => $amount,
                'reason' => $reason,
                'hold_total' => $wallet->hold_amount + $amount,
            ]);

            return true;
        });
    }

    /**
     * Release (cancel hold) funds
     *
     * @param int $walletId Wallet ID
     * @param int $amount Amount in kopeks
     * @param string $reason Operation reason
     * @param string $correlationId Tracing ID
     * @return bool
     * @throws \Exception
     */
    public function releaseHold(int $walletId, int $amount, string $reason, string $correlationId): bool
    {
        return $this->db->transaction(function () use ($walletId, $amount, $reason, $correlationId): bool {
            $wallet = Wallet::lockForUpdate()->findOrFail($walletId);

            if ($wallet->hold_amount < $amount) {
                throw new \Exception('Cannot release more than held');
            }

            // Update hold_amount
            $wallet->update([
                'hold_amount' => $wallet->hold_amount - $amount,
                'updated_at' => now(),
            ]);

            // Log transaction
            BalanceTransaction::create([
                'wallet_id' => $walletId,
                'type' => 'release',
                'amount' => $amount,
                'status' => 'completed',
                'reason' => $reason,
                'correlation_id' => $correlationId,
            ]);

            $this->log->channel('audit')->info('Release hold', [
                'correlation_id' => $correlationId,
                'wallet_id' => $walletId,
                'amount' => $amount,
                'reason' => $reason,
            ]);

            return true;
        });
    }

    /**
     * Debit (withdraw) funds
     *
     * @param int $walletId Wallet ID
     * @param int $amount Amount in kopeks
     * @param string $reason Operation reason
     * @param string $correlationId Tracing ID
     * @return bool
     * @throws \Exception
     */
    public function debit(int $walletId, int $amount, string $reason, string $correlationId): bool
    {
        return $this->db->transaction(function () use ($walletId, $amount, $reason, $correlationId): bool {
            $wallet = Wallet::lockForUpdate()->findOrFail($walletId);

            if ($wallet->current_balance < $amount) {
                throw new \Exception('Insufficient balance');
            }

            // Deduct from balance
            $wallet->update([
                'current_balance' => $wallet->current_balance - $amount,
                'updated_at' => now(),
            ]);

            // Log transaction
            BalanceTransaction::create([
                'wallet_id' => $walletId,
                'type' => 'debit',
                'amount' => $amount,
                'status' => 'completed',
                'reason' => $reason,
                'correlation_id' => $correlationId,
            ]);

            $this->log->channel('audit')->info('Debit funds', [
                'correlation_id' => $correlationId,
                'wallet_id' => $walletId,
                'amount' => $amount,
                'reason' => $reason,
                'new_balance' => $wallet->current_balance - $amount,
            ]);

            return true;
        });
    }

    /**
     * Credit (deposit) funds
     *
     * @param int $walletId Wallet ID
     * @param int $amount Amount in kopeks
     * @param string $reason Operation reason
     * @param string $correlationId Tracing ID
     * @return bool
     * @throws \Exception
     */
    public function credit(int $walletId, int $amount, string $reason, string $correlationId): bool
    {
        return $this->db->transaction(function () use ($walletId, $amount, $reason, $correlationId): bool {
            $wallet = Wallet::lockForUpdate()->findOrFail($walletId);

            // Add to balance
            $wallet->update([
                'current_balance' => $wallet->current_balance + $amount,
                'updated_at' => now(),
            ]);

            // Log transaction
            BalanceTransaction::create([
                'wallet_id' => $walletId,
                'type' => 'credit',
                'amount' => $amount,
                'status' => 'completed',
                'reason' => $reason,
                'correlation_id' => $correlationId,
            ]);

            $this->log->channel('audit')->info('Credit funds', [
                'correlation_id' => $correlationId,
                'wallet_id' => $walletId,
                'amount' => $amount,
                'reason' => $reason,
                'new_balance' => $wallet->current_balance + $amount,
            ]);

            return true;
        });
    }

    /**
     * Get current balance
     *
     * @param int $walletId Wallet ID
     * @return int Balance in kopeks
     */
    public function getBalance(int $walletId): int
    {
        $wallet = Wallet::findOrFail($walletId);
        return $wallet->current_balance;
    }

    /**
     * Get available balance (current - hold)
     *
     * @param int $walletId Wallet ID
     * @return int Available balance in kopeks
     */
    public function getAvailableBalance(int $walletId): int
    {
        $wallet = Wallet::findOrFail($walletId);
        return $wallet->current_balance - $wallet->hold_amount;
    }
}
