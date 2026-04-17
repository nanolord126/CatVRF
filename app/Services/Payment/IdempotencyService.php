<?php

declare(strict_types=1);

namespace App\Services\Payment;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Psr\Log\LoggerInterface;

/**
 * IdempotencyService - Redis-based idempotency with Lua scripts for atomicity.
 *
 * Prevents double-charge and duplicate operations using atomic Redis operations.
 * Uses Lua scripts to ensure atomicity even under high concurrency.
 *
 * @package App\Services\Payment
 */
final readonly class IdempotencyService
{
    private const DEFAULT_TTL_SECONDS = 86400; // 24 hours
    private const PREFIX = 'payment:idempotency:';

    /**
     * Lua script for atomic idempotency check-and-set.
     * Returns: 0 if key exists (idempotent hit), 1 if key was set (new operation).
     */
    private const LUA_CHECK_AND_SET = <<<'LUA'
        local key = KEYS[1]
        local value = ARGV[1]
        local ttl = tonumber(ARGV[2])
        
        if redis.call('EXISTS', key) == 1 then
            return 0
        end
        
        redis.call('SETEX', key, ttl, value)
        return 1
    LUA;

    /**
     * Lua script for atomic idempotency check-and-set with payload comparison.
     * Returns: 0 if key exists with same payload (idempotent hit), 
     *          -1 if key exists with different payload (conflict),
     *          1 if key was set (new operation).
     */
    private const LUA_CHECK_AND_SET_WITH_PAYLOAD = <<<'LUA'
        local key = KEYS[1]
        local value = ARGV[1]
        local ttl = tonumber(ARGV[2])
        
        local existing = redis.call('GET', key)
        if existing then
            if existing == value then
                return 0
            else
                return -1
            end
        end
        
        redis.call('SETEX', key, ttl, value)
        return 1
    LUA;

    public function __construct(
        private RedisFactory $redis,
        private CacheRepository $cache,
        private LoggerInterface $logger,
    ) {}

    /**
     * Check idempotency and store if new operation.
     *
     * @param string $operation Operation type (e.g., 'payment_init', 'wallet_debit')
     * @param string $idempotencyKey Unique key for idempotency
     * @param array $payload Operation payload for comparison
     * @param int $ttlSeconds TTL for the idempotency record
     * @return array|null Returns stored response data if idempotent hit, null if new operation
     * @throws \RuntimeException If idempotency key exists with different payload
     */
    public function check(
        string $operation,
        string $idempotencyKey,
        array $payload,
        int $ttlSeconds = self::DEFAULT_TTL_SECONDS,
    ): ?array {
        $key = $this->buildKey($operation, $idempotencyKey);
        $value = json_encode($payload, JSON_THROW_ON_ERROR);

        $result = $this->redis->connection()->eval(
            self::LUA_CHECK_AND_SET_WITH_PAYLOAD,
            1,
            $key,
            $value,
            $ttlSeconds,
        );

        return match ($result) {
            0 => $this->getStoredResponse($key), // Idempotent hit
            -1 => throw new \RuntimeException(
                sprintf(
                    'Idempotency conflict for key %s: payload mismatch',
                    $idempotencyKey,
                ),
            ),
            default => null, // New operation
        };
    }

    /**
     * Store response data for an idempotency key.
     *
     * @param string $operation Operation type
     * @param string $idempotencyKey Unique key for idempotency
     * @param array $response Response data to store
     * @param int $ttlSeconds TTL for the response record
     */
    public function storeResponse(
        string $operation,
        string $idempotencyKey,
        array $response,
        int $ttlSeconds = self::DEFAULT_TTL_SECONDS,
    ): void {
        $key = $this->buildKey($operation, $idempotencyKey);
        $responseKey = $key . ':response';

        $this->redis->connection()->setex(
            $responseKey,
            $ttlSeconds,
            json_encode($response, JSON_THROW_ON_ERROR),
        );

        $this->logger->debug('Idempotency response stored', [
            'operation' => $operation,
            'idempotency_key' => $idempotencyKey,
        ]);
    }

    /**
     * Get stored response data for an idempotency key.
     *
     * @param string $key Full Redis key
     * @return array|null Stored response data or null if not found
     */
    private function getStoredResponse(string $key): ?array
    {
        $responseKey = $key . ':response';
        $data = $this->redis->connection()->get($responseKey);

        if ($data === null) {
            return null;
        }

        $this->logger->info('Idempotency hit detected', [
            'key' => $key,
        ]);

        return json_decode($data, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Invalidate an idempotency key (for testing or manual cleanup).
     *
     * @param string $operation Operation type
     * @param string $idempotencyKey Unique key for idempotency
     */
    public function invalidate(string $operation, string $idempotencyKey): void
    {
        $key = $this->buildKey($operation, $idempotencyKey);
        $responseKey = $key . ':response';

        $this->redis->connection()->del($key, $responseKey);

        $this->logger->debug('Idempotency key invalidated', [
            'operation' => $operation,
            'idempotency_key' => $idempotencyKey,
        ]);
    }

    /**
     * Build full Redis key for idempotency.
     *
     * @param string $operation Operation type
     * @param string $idempotencyKey Unique key for idempotency
     * @return string Full Redis key
     */
    private function buildKey(string $operation, string $idempotencyKey): string
    {
        return self::PREFIX . $operation . ':' . $idempotencyKey;
    }

    /**
     * Check if an idempotency key exists (for monitoring/debugging).
     *
     * @param string $operation Operation type
     * @param string $idempotencyKey Unique key for idempotency
     * @return bool True if key exists
     */
    public function exists(string $operation, string $idempotencyKey): bool
    {
        $key = $this->buildKey($operation, $idempotencyKey);

        return (bool) $this->redis->connection()->exists($key);
    }
}
