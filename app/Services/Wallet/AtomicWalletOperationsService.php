<?php declare(strict_types=1);

namespace App\Services\Wallet;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Log\LogManager;
use App\Models\Wallet;
use App\Models\BalanceTransaction;

/**
 * Atomic Wallet Operations Service
 * 
 * Provides atomic wallet operations using Redis Lua scripts to prevent
 * race conditions and ensure balance integrity across concurrent operations.
 * 
 * This service handles:
 * - Atomic debit with balance check (no negative balance possible)
 * - Atomic credit operations
 * - Atomic hold/release operations
 * - All operations are atomic at Redis level before DB persistence
 */
final readonly class AtomicWalletOperationsService
{
    private const string BALANCE_PREFIX = 'wallet:balance:';
    private const string HOLD_PREFIX = 'wallet:hold:';
    private const int LOCK_TTL_SECONDS = 30;

    public function __construct(
        private readonly CacheRepository $cache,
        private readonly DatabaseManager $db,
        private readonly LogManager $logger,
    ) {}

    /**
     * Atomic debit operation with balance check
     * 
     * Uses Redis Lua script to atomically check balance and decrement.
     * Prevents race conditions where concurrent debits could cause negative balance.
     * 
     * @param int $walletId Wallet ID
     * @param int $amount Amount to debit (in smallest currency unit, e.g., kopecks)
     * @param string $correlationId Correlation ID for tracing
     * @param array $metadata Additional metadata for the transaction
     * @return array ['success' => bool, 'balance_before' => int, 'balance_after' => int, 'transaction_id' => int|null]
     * @throws \RuntimeException If insufficient balance
     */
    public function atomicDebit(
        int $walletId,
        int $amount,
        string $correlationId,
        array $metadata = []
    ): array {
        if ($amount <= 0) {
            throw new \InvalidArgumentException("Debit amount must be positive, got: {$amount}");
        }

        $balanceKey = $this->buildBalanceKey($walletId);

        // Lua script for atomic check-and-decrement
        $luaScript = <<<'LUA'
            local key = KEYS[1]
            local amount = tonumber(ARGV[1])
            local current = tonumber(redis.call('GET', key)) or 0
            
            if current < amount then
                return {0, current, current}
            end
            
            local new_balance = current - amount
            redis.call('SET', key, new_balance)
            return {1, current, new_balance}
        LUA;

        $result = $this->cache->getRedis()->eval(
            $luaScript,
            1,
            $balanceKey,
            $amount
        );

        $success = (bool) $result[0];
        $balanceBefore = (int) $result[1];
        $balanceAfter = (int) $result[2];

        if (!$success) {
            $this->logger->channel('audit')->warning('Atomic debit failed: insufficient balance', [
                'correlation_id' => $correlationId,
                'wallet_id' => $walletId,
                'requested_amount' => $amount,
                'available_balance' => $balanceBefore,
            ]);

            throw new \RuntimeException(
                "Insufficient balance: requested {$amount}, available {$balanceBefore}"
            );
        }

        // Persist to database
        $transaction = $this->db->transaction(function () use ($walletId, $amount, $balanceBefore, $balanceAfter, $correlationId, $metadata) {
            $wallet = Wallet::whereKey($walletId)->lockForUpdate()->firstOrFail();
            
            // Double-check DB balance matches Redis (should be in sync)
            if ($wallet->current_balance !== $balanceBefore) {
                $this->logger->channel('audit')->warning('Balance mismatch between Redis and DB', [
                    'correlation_id' => $correlationId,
                    'wallet_id' => $walletId,
                    'redis_balance' => $balanceBefore,
                    'db_balance' => $wallet->current_balance,
                ]);
                
                // Sync DB to Redis (Redis is source of truth)
                $wallet->current_balance = $balanceAfter;
                $wallet->save();
            } else {
                $wallet->decrement('current_balance', $amount);
            }

            $transaction = BalanceTransaction::create([
                'wallet_id' => $wallet->id,
                'tenant_id' => $wallet->tenant_id ?? '0',
                'type' => 'debit',
                'amount' => -$amount,
                'status' => BalanceTransaction::STATUS_COMPLETED,
                'correlation_id' => $correlationId,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'metadata' => $metadata,
            ]);

            return $transaction;
        });

        $this->logger->channel('audit')->info('Atomic debit completed', [
            'correlation_id' => $correlationId,
            'wallet_id' => $walletId,
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'transaction_id' => $transaction->id,
        ]);

        return [
            'success' => true,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'transaction_id' => $transaction->id,
        ];
    }

    /**
     * Atomic credit operation
     * 
     * @param int $walletId Wallet ID
     * @param int $amount Amount to credit (in smallest currency unit)
     * @param string $correlationId Correlation ID for tracing
     * @param array $metadata Additional metadata for the transaction
     * @return array ['success' => bool, 'balance_before' => int, 'balance_after' => int, 'transaction_id' => int|null]
     */
    public function atomicCredit(
        int $walletId,
        int $amount,
        string $correlationId,
        array $metadata = []
    ): array {
        if ($amount <= 0) {
            throw new \InvalidArgumentException("Credit amount must be positive, got: {$amount}");
        }

        $balanceKey = $this->buildBalanceKey($walletId);

        // Lua script for atomic increment
        $luaScript = <<<'LUA'
            local key = KEYS[1]
            local amount = tonumber(ARGV[1])
            local current = tonumber(redis.call('GET', key)) or 0
            
            local new_balance = current + amount
            redis.call('SET', key, new_balance)
            return {current, new_balance}
        LUA;

        $result = $this->cache->getRedis()->eval(
            $luaScript,
            1,
            $balanceKey,
            $amount
        );

        $balanceBefore = (int) $result[0];
        $balanceAfter = (int) $result[1];

        // Persist to database
        $transaction = $this->db->transaction(function () use ($walletId, $amount, $balanceBefore, $balanceAfter, $correlationId, $metadata) {
            $wallet = Wallet::whereKey($walletId)->lockForUpdate()->firstOrFail();
            
            if ($wallet->current_balance !== $balanceBefore) {
                $this->logger->channel('audit')->warning('Balance mismatch between Redis and DB (credit)', [
                    'correlation_id' => $correlationId,
                    'wallet_id' => $walletId,
                    'redis_balance' => $balanceBefore,
                    'db_balance' => $wallet->current_balance,
                ]);
                
                $wallet->current_balance = $balanceAfter;
                $wallet->save();
            } else {
                $wallet->increment('current_balance', $amount);
            }

            $transaction = BalanceTransaction::create([
                'wallet_id' => $wallet->id,
                'tenant_id' => $wallet->tenant_id ?? '0',
                'type' => 'credit',
                'amount' => $amount,
                'status' => BalanceTransaction::STATUS_COMPLETED,
                'correlation_id' => $correlationId,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'metadata' => $metadata,
            ]);

            return $transaction;
        });

        $this->logger->channel('audit')->info('Atomic credit completed', [
            'correlation_id' => $correlationId,
            'wallet_id' => $walletId,
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'transaction_id' => $transaction->id,
        ]);

        return [
            'success' => true,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'transaction_id' => $transaction->id,
        ];
    }

    /**
     * Atomic hold operation
     * 
     * Holds funds without deducting from available balance.
     * Used for reservation before final capture.
     * 
     * @param int $walletId Wallet ID
     * @param int $amount Amount to hold
     * @param string $correlationId Correlation ID for tracing
     * @param string $reason Reason for hold
     * @return array ['success' => bool, 'balance_before' => int, 'hold_before' => int, 'hold_after' => int]
     * @throws \RuntimeException If insufficient available balance
     */
    public function atomicHold(
        int $walletId,
        int $amount,
        string $correlationId,
        string $reason = ''
    ): array {
        if ($amount <= 0) {
            throw new \InvalidArgumentException("Hold amount must be positive, got: {$amount}");
        }

        $balanceKey = $this->buildBalanceKey($walletId);
        $holdKey = $this->buildHoldKey($walletId);

        // Lua script for atomic hold with balance check
        $luaScript = <<<'LUA'
            local balance_key = KEYS[1]
            local hold_key = KEYS[2]
            local amount = tonumber(ARGV[1])
            
            local balance = tonumber(redis.call('GET', balance_key)) or 0
            local hold = tonumber(redis.call('GET', hold_key)) or 0
            
            local available = balance - hold
            if available < amount then
                return {0, balance, hold, hold}
            end
            
            local new_hold = hold + amount
            redis.call('SET', hold_key, new_hold)
            return {1, balance, hold, new_hold}
        LUA;

        $result = $this->cache->getRedis()->eval(
            $luaScript,
            2,
            $balanceKey,
            $holdKey,
            $amount
        );

        $success = (bool) $result[0];
        $balance = (int) $result[1];
        $holdBefore = (int) $result[2];
        $holdAfter = (int) $result[3];

        if (!$success) {
            $available = $balance - $holdBefore;
            $this->logger->channel('audit')->warning('Atomic hold failed: insufficient available balance', [
                'correlation_id' => $correlationId,
                'wallet_id' => $walletId,
                'requested_amount' => $amount,
                'available_balance' => $available,
                'current_hold' => $holdBefore,
            ]);

            throw new \RuntimeException(
                "Cannot hold {$amount}: available balance is {$available}"
            );
        }

        // Persist to database
        $this->db->transaction(function () use ($walletId, $holdAfter, $correlationId, $reason) {
            $wallet = Wallet::whereKey($walletId)->lockForUpdate()->firstOrFail();
            $wallet->hold_amount = $holdAfter;
            $wallet->save();

            BalanceTransaction::create([
                'wallet_id' => $wallet->id,
                'tenant_id' => $wallet->tenant_id ?? '0',
                'type' => BalanceTransaction::TYPE_HOLD,
                'amount' => -$amount,
                'status' => BalanceTransaction::STATUS_PENDING,
                'correlation_id' => $correlationId,
                'reason' => $reason,
                'balance_before' => $wallet->current_balance,
                'balance_after' => $wallet->current_balance,
            ]);
        });

        $this->logger->channel('audit')->info('Atomic hold completed', [
            'correlation_id' => $correlationId,
            'wallet_id' => $walletId,
            'amount' => $amount,
            'balance' => $balance,
            'hold_before' => $holdBefore,
            'hold_after' => $holdAfter,
            'reason' => $reason,
        ]);

        return [
            'success' => true,
            'balance_before' => $balance,
            'hold_before' => $holdBefore,
            'hold_after' => $holdAfter,
        ];
    }

    /**
     * Atomic release operation
     * 
     * Releases previously held funds.
     * 
     * @param int $walletId Wallet ID
     * @param int $amount Amount to release
     * @param string $correlationId Correlation ID for tracing
     * @return array ['success' => bool, 'hold_before' => int, 'hold_after' => int]
     * @throws \RuntimeException If amount exceeds held amount
     */
    public function atomicRelease(
        int $walletId,
        int $amount,
        string $correlationId
    ): array {
        if ($amount <= 0) {
            throw new \InvalidArgumentException("Release amount must be positive, got: {$amount}");
        }

        $holdKey = $this->buildHoldKey($walletId);

        // Lua script for atomic release
        $luaScript = <<<'LUA'
            local key = KEYS[1]
            local amount = tonumber(ARGV[1])
            local hold = tonumber(redis.call('GET', key)) or 0
            
            if hold < amount then
                return {0, hold, hold}
            end
            
            local new_hold = hold - amount
            redis.call('SET', key, new_hold)
            return {1, hold, new_hold}
        LUA;

        $result = $this->cache->getRedis()->eval(
            $luaScript,
            1,
            $holdKey,
            $amount
        );

        $success = (bool) $result[0];
        $holdBefore = (int) $result[1];
        $holdAfter = (int) $result[2];

        if (!$success) {
            $this->logger->channel('audit')->warning('Atomic release failed: amount exceeds held', [
                'correlation_id' => $correlationId,
                'wallet_id' => $walletId,
                'requested_release' => $amount,
                'current_hold' => $holdBefore,
            ]);

            throw new \RuntimeException(
                "Cannot release {$amount}: held amount is {$holdBefore}"
            );
        }

        // Persist to database
        $this->db->transaction(function () use ($walletId, $holdAfter, $correlationId) {
            $wallet = Wallet::whereKey($walletId)->lockForUpdate()->firstOrFail();
            $wallet->hold_amount = $holdAfter;
            $wallet->save();

            BalanceTransaction::create([
                'wallet_id' => $wallet->id,
                'tenant_id' => $wallet->tenant_id ?? '0',
                'type' => BalanceTransaction::TYPE_RELEASE,
                'amount' => $amount,
                'status' => BalanceTransaction::STATUS_COMPLETED,
                'correlation_id' => $correlationId,
                'reason' => 'Hold released',
                'balance_before' => $wallet->current_balance,
                'balance_after' => $wallet->current_balance,
            ]);
        });

        $this->logger->channel('audit')->info('Atomic release completed', [
            'correlation_id' => $correlationId,
            'wallet_id' => $walletId,
            'amount' => $amount,
            'hold_before' => $holdBefore,
            'hold_after' => $holdAfter,
        ]);

        return [
            'success' => true,
            'hold_before' => $holdBefore,
            'hold_after' => $holdAfter,
        ];
    }

    /**
     * Get current balance from Redis (fast read)
     * 
     * @param int $walletId Wallet ID
     * @return int Current balance
     */
    public function getBalance(int $walletId): int
    {
        $balanceKey = $this->buildBalanceKey($walletId);
        $balance = $this->cache->get($balanceKey);

        if ($balance === null) {
            // Sync from DB if not in Redis
            $wallet = Wallet::whereKey($walletId)->firstOrFail();
            $balance = $wallet->current_balance;
            $this->cache->put($balanceKey, $balance, now()->addHours(24));
        }

        return (int) $balance;
    }

    /**
     * Sync wallet balance from DB to Redis
     * 
     * Use this to ensure Redis is consistent after any direct DB operations
     * 
     * @param int $walletId Wallet ID
     * @return void
     */
    public function syncFromDatabase(int $walletId): void
    {
        $wallet = Wallet::whereKey($walletId)->firstOrFail();
        $balanceKey = $this->buildBalanceKey($walletId);
        $holdKey = $this->buildHoldKey($walletId);

        $this->cache->put($balanceKey, $wallet->current_balance, now()->addHours(24));
        $this->cache->put($holdKey, $wallet->hold_amount, now()->addHours(24));

        $this->logger->channel('audit')->info('Wallet synced from DB to Redis', [
            'wallet_id' => $walletId,
            'balance' => $wallet->current_balance,
            'hold' => $wallet->hold_amount,
        ]);
    }

    /**
     * Build Redis key for wallet balance
     */
    private function buildBalanceKey(int $walletId): string
    {
        return self::BALANCE_PREFIX . $walletId;
    }

    /**
     * Build Redis key for wallet hold
     */
    private function buildHoldKey(int $walletId): string
    {
        return self::HOLD_PREFIX . $walletId;
    }
}
