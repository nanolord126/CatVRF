<?php declare(strict_types=1);

namespace App\Services\Wallet;

use App\Models\Wallet;
use App\Models\BalanceTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class WalletService
{
    public function getBalance(int|string $tenantId): int
    {
        $wallet = Wallet::where('tenant_id', (string) $tenantId)->firstOrFail();

        return $wallet->current_balance;
    }

    public function createWallet(
        int|string $tenantId,
        int|string $userId = 0,
        int $initialBalance = 0,
    ): Wallet {
        return DB::transaction(function () use ($tenantId, $initialBalance) {
            $correlationId = Str::uuid()->toString();

            Log::channel('audit')->info('Wallet created', [
                'correlation_id' => $correlationId,
                'tenant_id'      => $tenantId,
                'initial_balance' => $initialBalance,
            ]);

            return Wallet::create([
                'tenant_id'       => (string) $tenantId,
                'current_balance' => $initialBalance,
                'hold_amount'     => 0,
                'correlation_id'  => $correlationId,
                'uuid'            => Str::uuid()->toString(),
            ]);
        });
    }

    public function credit(
        int|string $tenantId = 0,
        int $amount = 0,
        string $type = 'deposit',
        ?int $sourceId = null,
        string $correlationId = '',
        ?string $reason = null,
        ?string $sourceType = null,
        int $walletId = 0,
    ): BalanceTransaction|bool {
        if ($amount <= 0) {
            throw new \InvalidArgumentException("Credit amount must be positive, got: {$amount}");
        }

        if ($walletId > 0) {
            DB::transaction(function () use ($walletId, $amount, $type, $sourceId, $correlationId, $reason, $sourceType) {
                $wallet = Wallet::whereKey($walletId)->lockForUpdate()->firstOrFail();

                $balanceBefore = $wallet->current_balance;
                $balanceAfter  = $balanceBefore + $amount;

                Log::channel('audit')->info('Wallet credit (by walletId)', [
                    'correlation_id' => $correlationId ?: Str::uuid()->toString(),
                    'wallet_id'      => $walletId,
                    'amount'         => $amount,
                    'balance_before' => $balanceBefore,
                    'balance_after'  => $balanceAfter,
                ]);

                BalanceTransaction::create([
                    'wallet_id'      => $wallet->id,
                    'tenant_id'      => $wallet->tenant_id ?? '0',
                    'type'           => $type,
                    'amount'         => $amount,
                    'source_id'      => $sourceId,
                    'source_type'    => $sourceType,
                    'status'         => BalanceTransaction::STATUS_COMPLETED,
                    'correlation_id' => $correlationId ?: Str::uuid()->toString(),
                    'reason'         => $reason,
                    'balance_before' => $balanceBefore,
                    'balance_after'  => $balanceAfter,
                ]);

                $wallet->increment('current_balance', $amount);
            });

            return true;
        }

        return DB::transaction(function () use ($tenantId, $amount, $type, $sourceId, $correlationId, $reason, $sourceType) {
            $wallet = Wallet::where('tenant_id', (string) $tenantId)->lockForUpdate()->firstOrFail();

            $balanceBefore = $wallet->current_balance;
            $balanceAfter = $balanceBefore + $amount;

            Log::channel('audit')->info('Wallet credit', [
                'correlation_id' => $correlationId,
                'tenant_id' => $tenantId,
                'amount' => $amount,
                'type' => $type,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
            ]);

            $transaction = BalanceTransaction::create([
                'wallet_id' => $wallet->id,
                'tenant_id' => $tenantId,
                'type' => $type,
                'amount' => $amount,
                'source_id' => $sourceId,
                'source_type' => $sourceType,
                'status' => BalanceTransaction::STATUS_COMPLETED,
                'correlation_id' => $correlationId ?: Str::uuid()->toString(),
                'reason' => $reason,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
            ]);

            $wallet->increment('current_balance', $amount);

            return $transaction;
        });
    }

    public function debit(
        int|string $tenantId = 0,
        int $amount = 0,
        string $type = 'withdrawal',
        ?int $sourceId = null,
        string $correlationId = '',
        ?string $reason = null,
        ?string $sourceType = null,
        int $walletId = 0,
    ): BalanceTransaction|bool {
        if ($walletId > 0) {
            DB::transaction(function () use ($walletId, $amount, $type, $sourceId, $correlationId, $reason, $sourceType) {
                $wallet = Wallet::whereKey($walletId)->lockForUpdate()->firstOrFail();

                if ($wallet->current_balance < $amount) {
                    Log::channel('audit')->warning('Insufficient balance (walletId path)', [
                        'correlation_id' => $correlationId,
                        'wallet_id'      => $walletId,
                        'required'       => $amount,
                        'available'      => $wallet->current_balance,
                    ]);
                    throw new \Exception('Insufficient balance');
                }

                $balanceBefore = $wallet->current_balance;
                $balanceAfter  = $balanceBefore - $amount;

                Log::channel('audit')->info('Wallet debit (by walletId)', [
                    'correlation_id' => $correlationId ?: Str::uuid()->toString(),
                    'wallet_id'      => $walletId,
                    'amount'         => $amount,
                    'balance_before' => $balanceBefore,
                    'balance_after'  => $balanceAfter,
                ]);

                BalanceTransaction::create([
                    'wallet_id'      => $wallet->id,
                    'tenant_id'      => $wallet->tenant_id ?? '0',
                    'type'           => $type,
                    'amount'         => -$amount,
                    'source_id'      => $sourceId,
                    'source_type'    => $sourceType,
                    'status'         => BalanceTransaction::STATUS_COMPLETED,
                    'correlation_id' => $correlationId ?: Str::uuid()->toString(),
                    'reason'         => $reason,
                    'balance_before' => $balanceBefore,
                    'balance_after'  => $balanceAfter,
                ]);

                $wallet->decrement('current_balance', $amount);
            });

            return true;
        }

        return DB::transaction(function () use ($tenantId, $amount, $type, $sourceId, $correlationId, $reason, $sourceType) {
            $wallet = Wallet::where('tenant_id', (string) $tenantId)->lockForUpdate()->firstOrFail();

            if ($wallet->current_balance < $amount) {
                Log::channel('audit')->warning('Insufficient balance', [
                    'correlation_id' => $correlationId,
                    'tenant_id' => $tenantId,
                    'required' => $amount,
                    'available' => $wallet->current_balance,
                ]);
                throw new \Exception('Insufficient balance');
            }

            $balanceBefore = $wallet->current_balance;
            $balanceAfter = $balanceBefore - $amount;

            Log::channel('audit')->info('Wallet debit', [
                'correlation_id' => $correlationId,
                'tenant_id' => $tenantId,
                'amount' => $amount,
                'type' => $type,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
            ]);

            $transaction = BalanceTransaction::create([
                'wallet_id' => $wallet->id,
                'tenant_id' => $tenantId,
                'type' => $type,
                'amount' => -$amount,
                'source_id' => $sourceId,
                'source_type' => $sourceType,
                'status' => BalanceTransaction::STATUS_COMPLETED,
                'correlation_id' => $correlationId ?: Str::uuid()->toString(),
                'reason' => $reason,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
            ]);

            $wallet->decrement('current_balance', $amount);

            return $transaction;
        });
    }

    public function hold(
        int|string $tenantId = 0,
        int $amount = 0,
        string $reason = '',
        string $correlationId = '',
        int $walletId = 0,
    ): bool {
        DB::transaction(function () use ($tenantId, $walletId, $amount, $reason, $correlationId) {
            $wallet = $walletId > 0
                ? Wallet::whereKey($walletId)->lockForUpdate()->firstOrFail()
                : Wallet::where('tenant_id', (string) $tenantId)->lockForUpdate()->firstOrFail();

            $available = $wallet->current_balance - $wallet->hold_amount;
            if ($amount > $available) {
                throw new \Exception("Cannot hold {$amount}: available balance is {$available}");
            }

            Log::channel('audit')->info('Wallet hold', [
                'correlation_id' => $correlationId,
                'tenant_id' => $tenantId,
                'amount' => $amount,
            ]);

            $wallet->increment('hold_amount', $amount);
        });

        return true;
    }

    public function release(
        int|string $tenantId = 0,
        int $amount = 0,
        string $correlationId = '',
        int $walletId = 0,
    ): bool {
        if ($walletId > 0) {
            DB::transaction(function () use ($walletId, $amount, $correlationId) {
                $wallet = Wallet::whereKey($walletId)->lockForUpdate()->firstOrFail();

                if ($amount > $wallet->hold_amount) {
                    throw new \Exception("Cannot release {$amount}: held amount is {$wallet->hold_amount}");
                }

                Log::channel('audit')->info('Wallet release', [
                    'correlation_id' => $correlationId,
                    'wallet_id'      => $walletId,
                    'amount'         => $amount,
                ]);

                $wallet->decrement('hold_amount', $amount);
            });

            return true;
        }

        $this->releaseHold($tenantId, $amount, '', $correlationId);

        return true;
    }

    public function releaseHold(
        int|string $tenantId,
        int $amount,
        string $reason = '',
        string $correlationId = '',
    ): void {
        DB::transaction(function () use ($tenantId, $amount, $reason, $correlationId) {
            $wallet = Wallet::where('tenant_id', (string) $tenantId)->lockForUpdate()->firstOrFail();

            if ($amount > $wallet->hold_amount) {
                throw new \Exception("Cannot release {$amount}: held amount is {$wallet->hold_amount}");
            }

            Log::channel('audit')->info('Wallet release', [
                'correlation_id' => $correlationId,
                'tenant_id' => $tenantId,
                'amount' => $amount,
            ]);

            $wallet->decrement('hold_amount', $amount);
        });
    }
}
