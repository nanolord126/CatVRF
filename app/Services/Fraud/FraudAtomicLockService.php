<?php declare(strict_types=1);

namespace App\Services\Fraud;

use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;

/**
 * Fraud Atomic Lock Service with Redis Lua Scripts
 * 
 * Provides atomic operations for fraud check + slot hold/payment
 * Prevents race conditions in high-concurrency scenarios
 */
final readonly class FraudAtomicLockService
{
    private const LOCK_TTL = 30; // 30 seconds
    private const SLOT_HOLD_TTL = 300; // 5 minutes

    // Lua script for atomic fraud check + slot hold
    private const LUA_FRAUD_CHECK_HOLD = <<<'LUA'
        local fraud_key = KEYS[1]
        local slot_key = KEYS[2]
        local user_id = ARGV[1]
        local fraud_score = tonumber(ARGV[2])
        local threshold = tonumber(ARGV[3])
        local hold_ttl = tonumber(ARGV[4])
        local lock_ttl = tonumber(ARGV[5])
        
        -- Check if slot is already held
        local current_holder = redis.call('HGET', slot_key, 'holder_id')
        if current_holder and current_holder ~= tostring(user_id) then
            return {0, 'slot_already_held', redis.call('HGET', slot_key, 'holder_id')}
        end
        
        -- Check fraud score
        if fraud_score >= threshold then
            return {0, 'fraud_blocked', tostring(fraud_score)}
        end
        
        -- Acquire lock and hold slot atomically
        local lock_key = 'fraud:lock:' .. user_id .. ':' .. slot_key
        local lock_acquired = redis.call('SET', lock_key, '1', 'NX', 'EX', lock_ttl)
        
        if not lock_acquired then
            return {0, 'lock_failed', 'concurrent_operation'}
        end
        
        -- Hold slot
        redis.call('HSET', slot_key, 
            'holder_id', user_id,
            'held_at', ARGV[6],
            'fraud_score', fraud_score
        )
        redis.call('EXPIRE', slot_key, hold_ttl)
        
        return {1, 'success', lock_key}
    LUA;

    // Lua script for atomic fraud check + payment
    private const LUA_FRAUD_CHECK_PAYMENT = <<<'LUA'
        local fraud_key = KEYS[1]
        local payment_key = KEYS[2]
        local user_id = ARGV[1]
        local fraud_score = tonumber(ARGV[2])
        local threshold = tonumber(ARGV[3])
        local lock_ttl = tonumber(ARGV[4])
        
        -- Check fraud score
        if fraud_score >= threshold then
            return {0, 'fraud_blocked', tostring(fraud_score)}
        end
        
        -- Acquire lock
        local lock_key = 'fraud:lock:' .. user_id .. ':' .. payment_key
        local lock_acquired = redis.call('SET', lock_key, '1', 'NX', 'EX', lock_ttl)
        
        if not lock_acquired then
            return {0, 'lock_failed', 'concurrent_operation'}
        end
        
        -- Mark payment as fraud-checked
        redis.call('HSET', payment_key, 
            'fraud_checked', '1',
            'fraud_score', fraud_score,
            'checked_at', ARGV[5]
        )
        
        return {1, 'success', lock_key}
    LUA;

    // Lua script for releasing lock
    private const LUA_RELEASE_LOCK = <<<'LUA'
        local lock_key = KEYS[1]
        local expected_value = ARGV[1]
        
        local current_value = redis.call('GET', lock_key)
        if current_value == expected_value or not expected_value then
            redis.call('DEL', lock_key)
            return 1
        end
        
        return 0
    LUA;

    public function __construct(
        private readonly RedisFactory $redis,
        private readonly LogManager $logger,
    ) {}

    /**
     * Atomic fraud check + slot hold
     * Returns ['success' => bool, 'lock_key' => string|null, 'reason' => string]
     */
    public function fraudCheckWithSlotHold(
        int $userId,
        string $slotKey,
        float $fraudScore,
        float $threshold = 0.85,
        ?string $correlationId = null,
    ): array {
        $correlationId ??= Str::uuid()->toString();
        $fraudKey = "fraud:check:{$userId}";
        
        $result = $this->redis->connection()->eval(
            self::LUA_FRAUD_CHECK_HOLD,
            2,
            $fraudKey,
            $slotKey,
            $userId,
            $fraudScore,
            $threshold,
            self::SLOT_HOLD_TTL,
            self::LOCK_TTL,
            now()->toIso8601String(),
        );

        $success = (bool) $result[0];
        $reason = $result[1];
        $lockKey = $success ? $result[2] : null;

        $this->logger->channel('fraud_alert')->info('Atomic fraud check + slot hold', [
            'correlation_id' => $correlationId,
            'user_id' => $userId,
            'slot_key' => $slotKey,
            'fraud_score' => $fraudScore,
            'success' => $success,
            'reason' => $reason,
        ]);

        return [
            'success' => $success,
            'lock_key' => $lockKey,
            'reason' => $reason,
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Atomic fraud check + payment
     * Returns ['success' => bool, 'lock_key' => string|null, 'reason' => string]
     */
    public function fraudCheckWithPayment(
        int $userId,
        string $paymentKey,
        float $fraudScore,
        float $threshold = 0.85,
        ?string $correlationId = null,
    ): array {
        $correlationId ??= Str::uuid()->toString();
        $fraudKey = "fraud:check:{$userId}";
        
        $result = $this->redis->connection()->eval(
            self::LUA_FRAUD_CHECK_PAYMENT,
            2,
            $fraudKey,
            $paymentKey,
            $userId,
            $fraudScore,
            $threshold,
            self::LOCK_TTL,
            now()->toIso8601String(),
        );

        $success = (bool) $result[0];
        $reason = $result[1];
        $lockKey = $success ? $result[2] : null;

        $this->logger->channel('fraud_alert')->info('Atomic fraud check + payment', [
            'correlation_id' => $correlationId,
            'user_id' => $userId,
            'payment_key' => $paymentKey,
            'fraud_score' => $fraudScore,
            'success' => $success,
            'reason' => $reason,
        ]);

        return [
            'success' => $success,
            'lock_key' => $lockKey,
            'reason' => $reason,
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Release atomic lock
     */
    public function releaseLock(string $lockKey, ?string $expectedValue = null): bool
    {
        $result = $this->redis->connection()->eval(
            self::LUA_RELEASE_LOCK,
            1,
            $lockKey,
            $expectedValue ?? '1',
        );

        return (bool) $result;
    }

    /**
     * Release slot hold
     */
    public function releaseSlotHold(string $slotKey, int $userId): bool
    {
        $currentHolder = $this->redis->connection()->hget($slotKey, 'holder_id');
        
        if ($currentHolder != $userId) {
            $this->logger->channel('fraud_alert')->warning('Attempt to release slot held by another user', [
                'slot_key' => $slotKey,
                'user_id' => $userId,
                'current_holder' => $currentHolder,
            ]);
            return false;
        }

        $this->redis->connection()->hdel($slotKey, 'holder_id', 'held_at', 'fraud_score');
        
        // Release associated lock
        $lockKey = "fraud:lock:{$userId}:{$slotKey}";
        $this->releaseLock($lockKey);

        return true;
    }

    /**
     * Check if slot is held
     */
    public function isSlotHeld(string $slotKey): bool
    {
        return (bool) $this->redis->connection()->hexists($slotKey, 'holder_id');
    }

    /**
     * Get slot holder info
     */
    public function getSlotHolder(string $slotKey): ?array
    {
        $data = $this->redis->connection()->hgetall($slotKey);
        
        if (empty($data)) {
            return null;
        }

        return [
            'holder_id' => $data['holder_id'] ?? null,
            'held_at' => $data['held_at'] ?? null,
            'fraud_score' => $data['fraud_score'] ?? null,
        ];
    }
}
