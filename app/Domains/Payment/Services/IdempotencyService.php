<?php

declare(strict_types=1);

namespace App\Domains\Payment\Services;

use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Psr\Log\LoggerInterface;

/**
 * IdempotencyService - Ensures exactly-once payment operations.
 *
 * CRITICAL: Uses Redis Lua scripts to prevent double-charge scenarios.
 * All payment operations must be idempotent at the gateway level.
 *
 * Architecture:
 * - Key format: payment:idempotency:{correlation_id}
 * - TTL: 24 hours (configurable)
 * - Returns existing payment ID if already processed
 *
 * @package App\Domains\Payment\Services
 */
final readonly class IdempotencyService
{
    private const KEY_PREFIX = 'payment:idempotency:';
    private const DEFAULT_TTL = 86400; // 24 hours

    /**
     * Lua script for atomic idempotency check-and-set.
     * Returns: nil if not exists, existing payment_id if exists.
     */
    private const LUA_IDEMPOTENCY_CHECK = <<<'LUA'
        local key = KEYS[1]
        local payment_id = ARGV[1]
        local ttl = tonumber(ARGV[2])
        
        local existing = redis.call('GET', key)
        if existing then
            return existing
        end
        
        redis.call('SETEX', key, ttl, payment_id)
        return nil
    LUA;

    public function __construct(
        private RedisFactory $redis,
        private LoggerInterface $logger,
    ) {}

    /**
     * Check if operation with given correlation ID was already processed.
     * If not, mark it as processing with the given payment ID.
     *
     * @param string $correlationId Unique correlation ID for the operation
     * @param int $paymentId Payment record ID to associate with this operation
     * @param int|null $ttl TTL in seconds (default: 24 hours)
     * @return int|null Existing payment ID if already processed, null otherwise
     */
    public function checkOrMark(string $correlationId, int $paymentId, ?int $ttl = null): ?int
    {
        $key = $this->key($correlationId);
        $ttl = $ttl ?? self::DEFAULT_TTL;

        $existingPaymentId = $this->redis->connection()->eval(
            self::LUA_IDEMPOTENCY_CHECK,
            1,
            $key,
            $paymentId,
            $ttl,
        );

        if ($existingPaymentId !== null) {
            $this->logger->info('Idempotency check: operation already processed', [
                'correlation_id' => $correlationId,
                'existing_payment_id' => $existingPaymentId,
                'new_payment_id' => $paymentId,
            ]);

            return (int) $existingPaymentId;
        }

        $this->logger->info('Idempotency check: operation marked as processing', [
            'correlation_id' => $correlationId,
            'payment_id' => $paymentId,
            'ttl' => $ttl,
        ]);

        return null;
    }

    /**
     * Manually mark an operation as processed (for idempotency key recreation).
     *
     * @param string $correlationId
     * @param int $paymentId
     * @param int|null $ttl
     * @return void
     */
    public function mark(string $correlationId, int $paymentId, ?int $ttl = null): void
    {
        $key = $this->key($correlationId);
        $ttl = $ttl ?? self::DEFAULT_TTL;

        $this->redis->connection()->setex($key, $ttl, $paymentId);

        $this->logger->info('Idempotency key manually set', [
            'correlation_id' => $correlationId,
            'payment_id' => $paymentId,
            'ttl' => $ttl,
        ]);
    }

    /**
     * Check if operation exists without marking it.
     *
     * @param string $correlationId
     * @return int|null Payment ID if exists, null otherwise
     */
    public function check(string $correlationId): ?int
    {
        $key = $this->key($correlationId);
        $paymentId = $this->redis->connection()->get($key);

        return $paymentId !== null ? (int) $paymentId : null;
    }

    /**
     * Delete idempotency key (use with caution - only for testing or manual recovery).
     *
     * @param string $correlationId
     * @return void
     */
    public function delete(string $correlationId): void
    {
        $key = $this->key($correlationId);
        $this->redis->connection()->del($key);

        $this->logger->warning('Idempotency key deleted', [
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Generate Redis key for correlation ID.
     */
    private function key(string $correlationId): string
    {
        return self::KEY_PREFIX . $correlationId;
    }
}
