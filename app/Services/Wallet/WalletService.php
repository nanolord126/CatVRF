<?php declare(strict_types=1);

namespace App\Services\Wallet;

use App\Models\BalanceTransaction;
use App\Models\Wallet;
use App\Services\FraudControlService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final readonly class WalletService
{
    public function __construct(
        private FraudControlService $fraudControl,
    ) {}

    public function getBalance(int|string $tenantId): int
    {
        $cacheKey = "wallet:balance:tenant:{$tenantId}";

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($tenantId) {
            $wallet = Wallet::where('tenant_id', (string) $tenantId)->firstOrFail();
            return $wallet->current_balance;
        });
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

        $correlationId = $correlationId ?: Str::uuid()->toString();

        if ($walletId > 0) {
            return $this->creditByWalletId($walletId, $amount, $type, $sourceId, $correlationId, $reason, $sourceType);
        }

        return DB::transaction(function () use ($tenantId, $amount, $type, $sourceId, $correlationId, $reason, $sourceType) {
            $wallet = Wallet::where('tenant_id', (string) $tenantId)->lockForUpdate()->firstOrFail();

            // Fraud check перед пополнением
            $fraudResult = $this->fraudControl->check(
                userId: (int) $tenantId,
                operationType: 'wallet_credit',
                amount: $amount,
                ipAddress: request()->ip(),
                deviceFingerprint: request()->header('X-Device-Fingerprint'),
                correlationId: $correlationId,
            );

            if ($fraudResult['decision'] === 'block') {
                Log::channel('fraud_alert')->warning('Wallet credit blocked by fraud check', [
                    'correlation_id' => $correlationId,
                    'tenant_id' => $tenantId,
                    'amount' => $amount,
                    'fraud_score' => $fraudResult['score'],
                ]);

                throw new \RuntimeException('Operation blocked by fraud detection system');
            }

            $balanceBefore = $wallet->current_balance;
            $balanceAfter = $balanceBefore + $amount;

            Log::channel('audit')->info('Wallet credit', [
                'correlation_id' => $correlationId,
                'tenant_id' => $tenantId,
                'amount' => $amount,
                'type' => $type,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'fraud_score' => $fraudResult['score'],
            ]);

            $transaction = BalanceTransaction::create([
                'wallet_id' => $wallet->id,
                'tenant_id' => $tenantId,
                'type' => $type,
                'amount' => $amount,
                'source_id' => $sourceId,
                'source_type' => $sourceType,
                'status' => BalanceTransaction::STATUS_COMPLETED,
                'correlation_id' => $correlationId,
                'reason' => $reason,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
            ]);

            $wallet->increment('current_balance', $amount);

            // Инвалидация кэша
            Cache::forget("wallet:balance:tenant:{$tenantId}");

            return $transaction;
        });
    }

    private function creditByWalletId(
        int $walletId,
        int $amount,
        string $type,
        ?int $sourceId,
        string $correlationId,
        ?string $reason,
        ?string $sourceType,
    ): bool {
        DB::transaction(function () use ($walletId, $amount, $type, $sourceId, $correlationId, $reason, $sourceType) {
            $wallet = Wallet::whereKey($walletId)->lockForUpdate()->firstOrFail();

            // Fraud check
            $fraudResult = $this->fraudControl->check(
                userId: (int) $wallet->tenant_id,
                operationType: 'wallet_credit',
                amount: $amount,
                ipAddress: request()->ip(),
                deviceFingerprint: request()->header('X-Device-Fingerprint'),
                correlationId: $correlationId,
            );

            if ($fraudResult['decision'] === 'block') {
                Log::channel('fraud_alert')->warning('Wallet credit blocked by fraud check (walletId)', [
                    'correlation_id' => $correlationId,
                    'wallet_id' => $walletId,
                    'amount' => $amount,
                    'fraud_score' => $fraudResult['score'],
                ]);

                throw new \RuntimeException('Operation blocked by fraud detection system');
            }

            $balanceBefore = $wallet->current_balance;
            $balanceAfter  = $balanceBefore + $amount;

            Log::channel('audit')->info('Wallet credit (by walletId)', [
                'correlation_id' => $correlationId,
                'wallet_id'      => $walletId,
                'amount'         => $amount,
                'balance_before' => $balanceBefore,
                'balance_after'  => $balanceAfter,
                'fraud_score' => $fraudResult['score'],
            ]);

            BalanceTransaction::create([
                'wallet_id'      => $wallet->id,
                'tenant_id'      => $wallet->tenant_id ?? '0',
                'type'           => $type,
                'amount'         => $amount,
                'source_id'      => $sourceId,
                'source_type'    => $sourceType,
                'status'         => BalanceTransaction::STATUS_COMPLETED,
                'correlation_id' => $correlationId,
                'reason'         => $reason,
                'balance_before' => $balanceBefore,
                'balance_after'  => $balanceAfter,
            ]);

            $wallet->increment('current_balance', $amount);

            // Инвалидация кэша
            if ($wallet->tenant_id) {
                Cache::forget("wallet:balance:tenant:{$wallet->tenant_id}");
            }
        });

        return true;
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
        if ($amount <= 0) {
            throw new \InvalidArgumentException("Debit amount must be positive, got: {$amount}");
        }

        $correlationId = $correlationId ?: Str::uuid()->toString();

        if ($walletId > 0) {
            return $this->debitByWalletId($walletId, $amount, $type, $sourceId, $correlationId, $reason, $sourceType);
        }

        return DB::transaction(function () use ($tenantId, $amount, $type, $sourceId, $correlationId, $reason, $sourceType) {
            $wallet = Wallet::where('tenant_id', (string) $tenantId)->lockForUpdate()->firstOrFail();

            // Проверка доступного баланса
            if ($wallet->current_balance < $amount) {
                Log::channel('audit')->warning('Insufficient balance', [
                    'correlation_id' => $correlationId,
                    'tenant_id' => $tenantId,
                    'required' => $amount,
                    'available' => $wallet->current_balance,
                ]);
                throw new \RuntimeException('Insufficient balance');
            }

            // Fraud check перед списанием
            $fraudResult = $this->fraudControl->check(
                userId: (int) $tenantId,
                operationType: 'wallet_debit',
                amount: $amount,
                ipAddress: request()->ip(),
                deviceFingerprint: request()->header('X-Device-Fingerprint'),
                correlationId: $correlationId,
            );

            if ($fraudResult['decision'] === 'block') {
                Log::channel('fraud_alert')->warning('Wallet debit blocked by fraud check', [
                    'correlation_id' => $correlationId,
                    'tenant_id' => $tenantId,
                    'amount' => $amount,
                    'fraud_score' => $fraudResult['score'],
                ]);

                throw new \RuntimeException('Operation blocked by fraud detection system');
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
                'fraud_score' => $fraudResult['score'],
            ]);

            $transaction = BalanceTransaction::create([
                'wallet_id' => $wallet->id,
                'tenant_id' => $tenantId,
                'type' => $type,
                'amount' => -$amount,
                'source_id' => $sourceId,
                'source_type' => $sourceType,
                'status' => BalanceTransaction::STATUS_COMPLETED,
                'correlation_id' => $correlationId,
                'reason' => $reason,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
            ]);

            $wallet->decrement('current_balance', $amount);

            // Инвалидация кэша
            Cache::forget("wallet:balance:tenant:{$tenantId}");

            return $transaction;
        });
    }

    private function debitByWalletId(
        int $walletId,
        int $amount,
        string $type,
        ?int $sourceId,
        string $correlationId,
        ?string $reason,
        ?string $sourceType,
    ): bool {
        DB::transaction(function () use ($walletId, $amount, $type, $sourceId, $correlationId, $reason, $sourceType) {
            $wallet = Wallet::whereKey($walletId)->lockForUpdate()->firstOrFail();

            if ($wallet->current_balance < $amount) {
                Log::channel('audit')->warning('Insufficient balance (walletId path)', [
                    'correlation_id' => $correlationId,
                    'wallet_id'      => $walletId,
                    'required'       => $amount,
                    'available'      => $wallet->current_balance,
                ]);
                throw new \RuntimeException('Insufficient balance');
            }

            // Fraud check
            $fraudResult = $this->fraudControl->check(
                userId: (int) $wallet->tenant_id,
                operationType: 'wallet_debit',
                amount: $amount,
                ipAddress: request()->ip(),
                deviceFingerprint: request()->header('X-Device-Fingerprint'),
                correlationId: $correlationId,
            );

            if ($fraudResult['decision'] === 'block') {
                Log::channel('fraud_alert')->warning('Wallet debit blocked by fraud check (walletId)', [
                    'correlation_id' => $correlationId,
                    'wallet_id'      => $walletId,
                    'amount'         => $amount,
                    'fraud_score' => $fraudResult['score'],
                ]);

                throw new \RuntimeException('Operation blocked by fraud detection system');
            }

            $balanceBefore = $wallet->current_balance;
            $balanceAfter  = $balanceBefore - $amount;

            Log::channel('audit')->info('Wallet debit (by walletId)', [
                'correlation_id' => $correlationId,
                'wallet_id'      => $walletId,
                'amount'         => $amount,
                'balance_before' => $balanceBefore,
                'balance_after'  => $balanceAfter,
                'fraud_score' => $fraudResult['score'],
            ]);

            BalanceTransaction::create([
                'wallet_id'      => $wallet->id,
                'tenant_id'      => $wallet->tenant_id ?? '0',
                'type'           => $type,
                'amount'         => -$amount,
                'source_id'      => $sourceId,
                'source_type'    => $sourceType,
                'status'         => BalanceTransaction::STATUS_COMPLETED,
                'correlation_id' => $correlationId,
                'reason'         => $reason,
                'balance_before' => $balanceBefore,
                'balance_after'  => $balanceAfter,
            ]);

            $wallet->decrement('current_balance', $amount);

            // Инвалидация кэша
            if ($wallet->tenant_id) {
                Cache::forget("wallet:balance:tenant:{$wallet->tenant_id}");
            }
        });

        return true;
    }

    public function hold(
        int|string $tenantId = 0,
        int $amount = 0,
        string $reason = '',
        string $correlationId = '',
        int $walletId = 0,
    ): bool {
        if ($amount <= 0) {
            throw new \InvalidArgumentException("Hold amount must be positive, got: {$amount}");
        }

        $correlationId = $correlationId ?: Str::uuid()->toString();

        DB::transaction(function () use ($tenantId, $walletId, $amount, $reason, $correlationId) {
            $wallet = $walletId > 0
                ? Wallet::whereKey($walletId)->lockForUpdate()->firstOrFail()
                : Wallet::where('tenant_id', (string) $tenantId)->lockForUpdate()->firstOrFail();

            $available = $wallet->current_balance - $wallet->hold_amount;
            if ($amount > $available) {
                Log::channel('audit')->warning('Insufficient available balance for hold', [
                    'correlation_id' => $correlationId,
                    'tenant_id' => $tenantId,
                    'wallet_id' => $walletId,
                    'requested_hold' => $amount,
                    'available_balance' => $available,
                ]);
                throw new \RuntimeException("Cannot hold {$amount}: available balance is {$available}");
            }

            Log::channel('audit')->info('Wallet hold', [
                'correlation_id' => $correlationId,
                'tenant_id' => $tenantId,
                'wallet_id' => $walletId,
                'amount' => $amount,
                'reason' => $reason,
                'hold_amount_before' => $wallet->hold_amount,
                'hold_amount_after' => $wallet->hold_amount + $amount,
            ]);

            // Создание транзакции холда
            BalanceTransaction::create([
                'wallet_id' => $wallet->id,
                'tenant_id' => $wallet->tenant_id ?? (string) $tenantId,
                'type' => BalanceTransaction::TYPE_HOLD,
                'amount' => -$amount,
                'status' => BalanceTransaction::STATUS_PENDING,
                'correlation_id' => $correlationId,
                'reason' => $reason,
                'balance_before' => $wallet->current_balance,
                'balance_after' => $wallet->current_balance,
                'tags' => ['hold_amount' => $amount],
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
        if ($amount <= 0) {
            throw new \InvalidArgumentException("Release amount must be positive, got: {$amount}");
        }

        $correlationId = $correlationId ?: Str::uuid()->toString();

        DB::transaction(function () use ($tenantId, $walletId, $amount, $correlationId) {
            $wallet = $walletId > 0
                ? Wallet::whereKey($walletId)->lockForUpdate()->firstOrFail()
                : Wallet::where('tenant_id', (string) $tenantId)->lockForUpdate()->firstOrFail();

            if ($amount > $wallet->hold_amount) {
                Log::channel('audit')->warning('Cannot release more than held amount', [
                    'correlation_id' => $correlationId,
                    'tenant_id' => $tenantId,
                    'wallet_id' => $walletId,
                    'requested_release' => $amount,
                    'current_hold' => $wallet->hold_amount,
                ]);
                throw new \RuntimeException("Cannot release {$amount}: held amount is {$wallet->hold_amount}");
            }

            Log::channel('audit')->info('Wallet release', [
                'correlation_id' => $correlationId,
                'tenant_id' => $tenantId,
                'wallet_id' => $walletId,
                'amount' => $amount,
                'hold_amount_before' => $wallet->hold_amount,
                'hold_amount_after' => $wallet->hold_amount - $amount,
            ]);

            // Создание транзакции release
            BalanceTransaction::create([
                'wallet_id' => $wallet->id,
                'tenant_id' => $wallet->tenant_id ?? (string) $tenantId,
                'type' => BalanceTransaction::TYPE_RELEASE,
                'amount' => $amount,
                'status' => BalanceTransaction::STATUS_COMPLETED,
                'correlation_id' => $correlationId,
                'reason' => 'Hold released',
                'balance_before' => $wallet->current_balance,
                'balance_after' => $wallet->current_balance,
                'tags' => ['released_amount' => $amount],
            ]);

            $wallet->decrement('hold_amount', $amount);
        });

        return true;
    }

    public function releaseHold(
        int|string $tenantId,
        int $amount,
        string $reason = '',
        string $correlationId = '',
    ): void {
        $this->release($tenantId, $amount, $correlationId ?: Str::uuid()->toString());
    }
}
