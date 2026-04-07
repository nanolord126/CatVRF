<?php

declare(strict_types=1);

namespace Modules\Finances\Services;

use App\Services\FraudControlService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Modules\Finances\Data\BalanceTransactionData;
use Modules\Finances\Enums\BalanceTransactionStatus;
use Modules\Finances\Enums\BalanceTransactionType;
use Modules\Finances\Exceptions\InsufficientFundsException;
use Modules\Finances\Exceptions\WalletException;
use Modules\Finances\Interfaces\WalletServiceInterface;
use Modules\Finances\Models\BalanceTransaction;
use Modules\Finances\Models\Wallet;

final class WalletService implements WalletServiceInterface
{
    public function __construct(
        private readonly FraudControlService $fraudControl,
    ) {
    }

    public function createWallet(int $tenantId, ?int $businessGroupId, string $correlationId): Wallet
    {
        FraudControlService::check();

        return DB::transaction(function () use ($tenantId, $businessGroupId, $correlationId) {
            $wallet = Wallet::create([
                'tenant_id' => $tenantId,
                'business_group_id' => $businessGroupId,
                'uuid' => Str::uuid()->toString(),
                'correlation_id' => $correlationId,
                'current_balance' => 0,
                'hold_amount' => 0,
                'tags' => ['initial_creation'],
            ]);

            Log::channel('audit')->info('Wallet created.', [
                'wallet_id' => $wallet->id,
                'tenant_id' => $tenantId,
                'business_group_id' => $businessGroupId,
                'correlation_id' => $correlationId,
            ]);

            return $wallet;
        });
    }

    public function getWalletByTenant(int $tenantId): ?Wallet
    {
        return Wallet::where('tenant_id', $tenantId)->first();
    }

    public function getWalletByBusinessGroup(int $businessGroupId): ?Wallet
    {
        return Wallet::where('business_group_id', $businessGroupId)->first();
    }

    public function credit(int $walletId, int $amount, BalanceTransactionType $type, string $correlationId, ?array $meta = null): BalanceTransactionData
    {
        return $this->createTransaction($walletId, $amount, $type, $correlationId, $meta);
    }

    public function debit(int $walletId, int $amount, BalanceTransactionType $type, string $correlationId, ?array $meta = null): BalanceTransactionData
    {
        if ($amount <= 0) {
            throw new WalletException('Debit amount must be positive.');
        }
        return $this->createTransaction($walletId, -$amount, $type, $correlationId, $meta);
    }

    public function hold(int $walletId, int $amount, string $correlationId, ?array $meta = null): bool
    {
        FraudControlService::check();
        RateLimiter::hit('wallet-operation:' . $walletId);

        if ($amount <= 0) {
            throw new WalletException('Hold amount must be positive.');
        }

        return DB::transaction(function () use ($walletId, $amount, $correlationId) {
            $wallet = Wallet::lockForUpdate()->findOrFail($walletId);

            if ($wallet->current_balance < $amount) {
                throw new InsufficientFundsException('Insufficient funds to hold.');
            }

            $wallet->current_balance -= $amount;
            $wallet->hold_amount += $amount;
            $wallet->save();

            Log::channel('audit')->info('Wallet hold created.', [
                'wallet_id' => $walletId,
                'amount' => $amount,
                'correlation_id' => $correlationId,
            ]);

            $this->invalidateBalanceCache($walletId);

            return true;
        });
    }

    public function releaseHold(int $walletId, int $amount, string $correlationId, ?array $meta = null): bool
    {
        FraudControlService::check();

        if ($amount <= 0) {
            throw new WalletException('Release amount must be positive.');
        }

        return DB::transaction(function () use ($walletId, $amount, $correlationId) {
            $wallet = Wallet::lockForUpdate()->findOrFail($walletId);

            if ($wallet->hold_amount < $amount) {
                throw new WalletException('Not enough hold amount to release.');
            }

            $wallet->hold_amount -= $amount;
            $wallet->current_balance += $amount;
            $wallet->save();

            Log::channel('audit')->info('Wallet hold released.', [
                'wallet_id' => $walletId,
                'amount' => $amount,
                'correlation_id' => $correlationId,
            ]);

            $this->invalidateBalanceCache($walletId);

            return true;
        });
    }

    public function getBalance(int $walletId): int
    {
        return Cache::remember(
            "wallet:balance:{$walletId}",
            300,
            fn () => Wallet::findOrFail($walletId)->current_balance
        );
    }

    public function getTransactions(int $walletId): Collection
    {
        return BalanceTransaction::where('wallet_id', $walletId)
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn (BalanceTransaction $tx) => BalanceTransactionData::from($tx));
    }

    private function createTransaction(int $walletId, int $amount, BalanceTransactionType $type, string $correlationId, ?array $meta): BalanceTransactionData
    {
        FraudControlService::check();
        RateLimiter::hit('wallet-operation:' . $walletId);

        $transaction = DB::transaction(function () use ($walletId, $amount, $type, $correlationId, $meta) {
            $wallet = Wallet::lockForUpdate()->findOrFail($walletId);

            if ($amount < 0 && $wallet->current_balance < abs($amount)) {
                throw new InsufficientFundsException('Insufficient funds for this operation.');
            }

            $wallet->current_balance += $amount;
            $wallet->save();

            $balanceTransaction = BalanceTransaction::create([
                'wallet_id' => $walletId,
                'uuid' => Str::uuid()->toString(),
                'correlation_id' => $correlationId,
                'type' => $type,
                'amount' => $amount,
                'balance_after' => $wallet->current_balance,
                'status' => BalanceTransactionStatus::COMPLETED,
                'meta' => $meta,
                'created_at' => now(),
            ]);

            Log::channel('audit')->info('Balance transaction created.', [
                'transaction_id' => $balanceTransaction->id,
                'wallet_id' => $walletId,
                'amount' => $amount,
                'type' => $type->value,
                'correlation_id' => $correlationId,
            ]);

            $this->invalidateBalanceCache($walletId);

            return $balanceTransaction;
        });

        return BalanceTransactionData::from($transaction);
    }

    private function invalidateBalanceCache(int $walletId): void
    {
        Cache::forget("wallet:balance:{$walletId}");
    }
}

            $wallet->current_balance += $amount;
            $wallet->save();

            $txn = $wallet->transactions()->create([
                'uuid' => Str::uuid()->toString(),
                'type' => $type,
                'amount' => $amount,
                'correlation_id' => $this->correlationId,
                'meta' => $meta,
            ]);

            Log::channel('audit')->info('Balance transaction created.', [
                'wallet_id' => $walletId,
                'transaction_id' => $txn->id,
                'type' => $type->value,
                'amount' => $amount,
                'new_balance' => $wallet->current_balance,
                'correlation_id' => $this->correlationId,
            ]);

            return $txn;
        });

        return BalanceTransactionData::from($transaction);
    }
}
