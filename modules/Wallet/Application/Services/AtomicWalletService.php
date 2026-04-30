<?php

declare(strict_types=1);

namespace Modules\Wallet\Application\Services;

use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Database\DatabaseManager;
use Modules\Wallet\Domain\Entities\Wallet as DomainWallet;
use Modules\Wallet\Domain\Enums\BalanceTransactionType;
use Modules\Wallet\Infrastructure\Models\Wallet;
use Psr\Log\LoggerInterface;

/**
 * AtomicWalletService - Wallet operations with atomic Redis Lua scripts.
 *
 * CRITICAL: Uses Redis Lua scripts to prevent race conditions in debit/credit operations.
 * No negative balance possible due to atomic checks.
 */
final readonly class AtomicWalletService
{
    private const PREFIX = 'wallet:balance:';
    private const LOCK_PREFIX = 'wallet:lock:';

    /**
     * Lua script for atomic debit with balance check.
     * Returns: 0 if insufficient balance, 1 if successful.
     */
    private const LUA_ATOMIC_DEBIT = <<<'LUA'
        local balance_key = KEYS[1]
        local amount = tonumber(ARGV[1])
        local new_balance = tonumber(ARGV[2])
        
        local current = tonumber(redis.call('GET', balance_key))
        if not current then
            current = 0
        end
        
        if current < amount then
            return 0
        end
        
        redis.call('SET', balance_key, new_balance)
        return 1
    LUA;

    /**
     * Lua script for atomic credit.
     * Returns: 1 (always successful).
     */
    private const LUA_ATOMIC_CREDIT = <<<'LUA'
        local balance_key = KEYS[1]
        local new_balance = tonumber(ARGV[1])
        
        redis.call('SET', balance_key, new_balance)
        return 1
    LUA;

    /**
     * Lua script for atomic hold operation.
     * Returns: 0 if insufficient balance, 1 if successful.
     */
    private const LUA_ATOMIC_HOLD = <<<'LUA'
        local balance_key = KEYS[1]
        local hold_key = KEYS[2]
        local amount = tonumber(ARGV[1])
        local new_balance = tonumber(ARGV[2])
        local new_hold = tonumber(ARGV[3])
        
        local current = tonumber(redis.call('GET', balance_key))
        if not current then
            current = 0
        end
        
        if current < amount then
            return 0
        end
        
        redis.call('SET', balance_key, new_balance)
        redis.call('SET', hold_key, new_hold)
        return 1
    LUA;

    /** Transaction types valid for credit operations. */
    private const CREDIT_TYPES = [
        BalanceTransactionType::DEPOSIT,
        BalanceTransactionType::BONUS,
        BalanceTransactionType::REFUND,
        BalanceTransactionType::COMMISSION,
        BalanceTransactionType::RELEASE_HOLD,
    ];

    /** Transaction types valid for debit operations. */
    private const DEBIT_TYPES = [
        BalanceTransactionType::WITHDRAWAL,
        BalanceTransactionType::HOLD,
        BalanceTransactionType::PAYOUT,
        BalanceTransactionType::COMMISSION,
    ];

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly Guard $guard,
        private readonly FraudControlService $fraud,
        private readonly AuditService $audit,
        private readonly RedisFactory $redis,
    ) {}

    /**
     * Credit wallet with atomic Redis operation.
     */
    public function credit(
        int $walletId,
        int $amount,
        BalanceTransactionType $type,
        string $correlationId,
        ?string $sourceType = null,
        ?int $sourceId = null,
        ?array $metadata = null,
    ): Wallet {
        $this->guardAmount($amount);
        $this->guardCreditType($type);

        $userId = $this->getCurrentUserId() ?? 0;
        $this->fraud->check($userId, "wallet_credit_{$type->value}", $amount, null, null, $correlationId);

        return $this->db->transaction(function () use (
            $walletId,
            $amount,
            $type,
            $correlationId,
            $sourceType,
            $sourceId,
            $metadata
        ): Wallet {
            /** @var Wallet $wallet */
            $wallet = Wallet::query()->lockForUpdate()->findOrFail($walletId);
            $oldBalance = $wallet->current_balance;

            $newBalance = $oldBalance + $amount;

            // Atomic Redis update
            $balanceKey = $this->balanceKey($walletId);
            $this->redis->connection()->eval(
                self::LUA_ATOMIC_CREDIT,
                1,
                $balanceKey,
                $newBalance,
            );

            $wallet->current_balance = $newBalance;

            if ($type === BalanceTransactionType::RELEASE_HOLD) {
                if ($wallet->hold_amount < $amount) {
                    throw new \RuntimeException('Not enough hold amount to release.');
                }
                $wallet->hold_amount -= $amount;
            }

            $wallet->save();

            $wallet->balanceTransactions()->create([
                'type' => $type->value,
                'amount' => $amount,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'correlation_id' => $correlationId,
                'metadata' => $metadata,
            ]);

            $this->audit->logAction(
                'wallet_credited',
                'Wallet',
                $wallet->id,
                ['current_balance' => $oldBalance],
                ['current_balance' => $wallet->current_balance],
                $correlationId
            );

            $this->logger->info('Wallet credited (atomic)', [
                'wallet_id' => $wallet->id,
                'amount' => $amount,
                'type' => $type->value,
                'correlation_id' => $correlationId,
            ]);

            return $wallet;
        });
    }

    /**
     * Debit wallet with atomic Redis operation.
     *
     * CRITICAL: Uses Lua script to prevent race conditions and negative balance.
     *
     * @throws \RuntimeException If insufficient balance
     */
    public function debit(
        int $walletId,
        int $amount,
        BalanceTransactionType $type,
        string $correlationId,
        ?string $sourceType = null,
        ?int $sourceId = null,
        ?string $verticalCode = null,
        ?array $metadata = null,
    ): Wallet {
        $this->guardAmount($amount);
        $this->guardDebitType($type);

        $userId = $this->getCurrentUserId() ?? 0;
        $this->fraud->check($userId, "wallet_debit_{$type->value}", $amount, null, null, $correlationId);

        return $this->db->transaction(function () use (
            $walletId,
            $amount,
            $type,
            $correlationId,
            $sourceType,
            $sourceId,
            $metadata
        ): Wallet {
            /** @var Wallet $wallet */
            $wallet = Wallet::query()->lockForUpdate()->findOrFail($walletId);
            $oldBalance = $wallet->current_balance;

            $newBalance = $oldBalance - $amount;

            // Atomic Redis debit with balance check
            $balanceKey = $this->balanceKey($walletId);
            $result = $this->redis->connection()->eval(
                self::LUA_ATOMIC_DEBIT,
                1,
                $balanceKey,
                $amount,
                $newBalance,
            );

            if ($result === 0) {
                throw new \RuntimeException('Insufficient balance (atomic check failed).');
            }

            $wallet->current_balance = $newBalance;
            $wallet->save();

            $wallet->balanceTransactions()->create([
                'type' => $type->value,
                'amount' => $amount,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'correlation_id' => $correlationId,
                'metadata' => $metadata,
            ]);

            $this->audit->logAction(
                'wallet_debited',
                'Wallet',
                $wallet->id,
                ['current_balance' => $oldBalance],
                ['current_balance' => $wallet->current_balance],
                $correlationId
            );

            $this->logger->info('Wallet debited (atomic)', [
                'wallet_id' => $wallet->id,
                'amount' => $amount,
                'type' => $type->value,
                'correlation_id' => $correlationId,
            ]);

            return $wallet;
        });
    }

    /**
     * Hold amount in wallet with atomic Redis operation.
     *
     * CRITICAL: Uses Lua script to prevent race conditions.
     *
     * @throws \RuntimeException If insufficient balance
     */
    public function hold(
        int $walletId,
        int $amount,
        string $correlationId,
        ?string $sourceType = null,
        ?int $sourceId = null,
        ?array $metadata = null,
        ?string $verticalCode = null,
    ): Wallet {
        $this->guardAmount($amount);

        $userId = $this->getCurrentUserId() ?? 0;
        $this->fraud->check($userId, 'wallet_hold', $amount, null, null, $correlationId);

        return $this->db->transaction(function () use (
            $walletId,
            $amount,
            $correlationId,
            $sourceType,
            $sourceId,
            $metadata,
        ): Wallet {
            /** @var Wallet $wallet */
            $wallet = Wallet::query()->lockForUpdate()->findOrFail($walletId);
            $oldBalance = $wallet->current_balance;
            $oldHold = $wallet->hold_amount;

            $newBalance = $oldBalance - $amount;
            $newHold = $oldHold + $amount;

            // Atomic Redis hold with balance check
            $balanceKey = $this->balanceKey($walletId);
            $holdKey = $this->holdKey($walletId);
            $result = $this->redis->connection()->eval(
                self::LUA_ATOMIC_HOLD,
                2,
                $balanceKey,
                $holdKey,
                $amount,
                $newBalance,
                $newHold,
            );

            if ($result === 0) {
                throw new \RuntimeException('Insufficient balance for hold (atomic check failed).');
            }

            $wallet->current_balance = $newBalance;
            $wallet->hold_amount = $newHold;
            $wallet->save();

            $wallet->balanceTransactions()->create([
                'type' => BalanceTransactionType::HOLD->value,
                'amount' => $amount,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'correlation_id' => $correlationId,
                'metadata' => $metadata,
            ]);

            $this->audit->logAction(
                'wallet_hold',
                'Wallet',
                $wallet->id,
                ['current_balance' => $oldBalance, 'hold_amount' => $oldHold],
                ['current_balance' => $wallet->current_balance, 'hold_amount' => $wallet->hold_amount],
                $correlationId
            );

            $this->logger->info('Wallet amount held (atomic)', [
                'wallet_id' => $wallet->id,
                'amount' => $amount,
                'correlation_id' => $correlationId,
            ]);

            return $wallet;
        });
    }

    /**
     * Get cached balance from Redis.
     *
     * @return int|null Balance in kopecks or null if not cached
     */
    public function getCachedBalance(int $walletId): ?int
    {
        $balanceKey = $this->balanceKey($walletId);
        $balance = $this->redis->connection()->get($balanceKey);

        return $balance !== null ? (int) $balance : null;
    }

    /**
     * Sync Redis cache with database balance.
     */
    public function syncCache(int $walletId): void
    {
        /** @var Wallet $wallet */
        $wallet = Wallet::findOrFail($walletId);

        $balanceKey = $this->balanceKey($walletId);
        $holdKey = $this->holdKey($walletId);

        $this->redis->connection()->set($balanceKey, $wallet->current_balance);
        $this->redis->connection()->set($holdKey, $wallet->hold_amount);

        $this->logger->debug('Wallet cache synced', [
            'wallet_id' => $walletId,
            'balance' => $wallet->current_balance,
            'hold' => $wallet->hold_amount,
        ]);
    }

    // ─── Guard helpers ────────────────────────────────────────────────

    private function guardAmount(int $amount): void
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be positive');
        }
    }

    private function guardCreditType(BalanceTransactionType $type): void
    {
        if (! \in_array($type, self::CREDIT_TYPES, true)) {
            throw new \InvalidArgumentException("Invalid credit type: {$type->value}");
        }
    }

    private function guardDebitType(BalanceTransactionType $type): void
    {
        if (! \in_array($type, self::DEBIT_TYPES, true)) {
            throw new \InvalidArgumentException("Invalid debit type: {$type->value}");
        }
    }

    private function getCurrentUserId(): ?int
    {
        $user = $this->guard->user();

        return $user ? (int) $user->getAuthIdentifier() : null;
    }

    private function balanceKey(int $walletId): string
    {
        return self::PREFIX.$walletId;
    }

    private function holdKey(int $walletId): string
    {
        return self::PREFIX.$walletId.':hold';
    }
}
