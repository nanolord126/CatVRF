<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\Facades\Redis;
use Psr\Log\LoggerInterface;

/**
 * RedisDistributedLockService - provides distributed locking using Redis.
 *
 * Prevents race conditions when multiple processes try to reserve the same inventory.
 * Uses Redis SET with NX and EX options for atomic lock acquisition.
 */
final readonly class RedisDistributedLockService
{
    private const LOCK_PREFIX = 'inventory:lock:';
    private const DEFAULT_TTL = 30; // 30 seconds default TTL

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Acquire a distributed lock for a specific resource.
     *
     * @param  string  $resourceKey The unique key for the resource (e.g., "inventory:product:100:warehouse:1")
     * @param  int  $ttl Time to live in seconds
     * @param  string  $ownerId Unique identifier for the lock owner (e.g., request ID)
     * @return bool True if lock was acquired, false otherwise
     */
    public function acquireLock(string $resourceKey, int $ttl = self::DEFAULT_TTL, string $ownerId = ''): bool
    {
        $lockKey = self::LOCK_PREFIX . $resourceKey;
        $lockValue = $ownerId ?: $this->generateLockValue();

        try {
            $result = Redis::set($lockKey, $lockValue, 'EX', $ttl, 'NX');

            if ($result === true || $result === 'OK') {
                $this->logger->debug('Distributed lock acquired', [
                    'resource_key' => $resourceKey,
                    'lock_key' => $lockKey,
                    'owner_id' => $lockValue,
                    'ttl' => $ttl,
                ]);

                return true;
            }

            $this->logger->debug('Failed to acquire distributed lock (already held)', [
                'resource_key' => $resourceKey,
                'lock_key' => $lockKey,
            ]);

            return false;
        } catch (\Throwable $e) {
            $this->logger->error('Error acquiring distributed lock', [
                'resource_key' => $resourceKey,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Release a distributed lock.
     *
     * Uses Lua script to ensure only the owner can release the lock.
     *
     * @param  string  $resourceKey The unique key for the resource
     * @param  string  $ownerId The owner ID that acquired the lock
     * @return bool True if lock was released, false otherwise
     */
    public function releaseLock(string $resourceKey, string $ownerId): bool
    {
        $lockKey = self::LOCK_PREFIX . $resourceKey;

        try {
            // Lua script to ensure atomic check-and-delete
            $script = <<<'LUA'
                if redis.call("GET", KEYS[1]) == ARGV[1] then
                    return redis.call("DEL", KEYS[1])
                else
                    return 0
                end
            LUA;

            $result = Redis::eval($script, 1, $lockKey, $ownerId);

            if ($result > 0) {
                $this->logger->debug('Distributed lock released', [
                    'resource_key' => $resourceKey,
                    'lock_key' => $lockKey,
                    'owner_id' => $ownerId,
                ]);

                return true;
            }

            $this->logger->warning('Failed to release distributed lock (not owner or expired)', [
                'resource_key' => $resourceKey,
                'lock_key' => $lockKey,
                'owner_id' => $ownerId,
            ]);

            return false;
        } catch (\Throwable $e) {
            $this->logger->error('Error releasing distributed lock', [
                'resource_key' => $resourceKey,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Extend the TTL of an existing lock.
     *
     * @param  string  $resourceKey The unique key for the resource
     * @param  int  $ttl New TTL in seconds
     * @param  string  $ownerId The owner ID that acquired the lock
     * @return bool True if lock was extended, false otherwise
     */
    public function extendLock(string $resourceKey, int $ttl, string $ownerId): bool
    {
        $lockKey = self::LOCK_PREFIX . $resourceKey;

        try {
            // Lua script to ensure only the owner can extend the lock
            $script = <<<'LUA'
                if redis.call("GET", KEYS[1]) == ARGV[1] then
                    return redis.call("EXPIRE", KEYS[1], ARGV[2])
                else
                    return 0
                end
            LUA;

            $result = Redis::eval($script, 1, $lockKey, $ownerId, $ttl);

            if ($result > 0) {
                $this->logger->debug('Distributed lock extended', [
                    'resource_key' => $resourceKey,
                    'lock_key' => $lockKey,
                    'owner_id' => $ownerId,
                    'new_ttl' => $ttl,
                ]);

                return true;
            }

            return false;
        } catch (\Throwable $e) {
            $this->logger->error('Error extending distributed lock', [
                'resource_key' => $resourceKey,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Check if a lock is currently held.
     *
     * @param  string  $resourceKey The unique key for the resource
     * @return bool True if lock exists, false otherwise
     */
    public function isLocked(string $resourceKey): bool
    {
        $lockKey = self::LOCK_PREFIX . $resourceKey;

        try {
            return Redis::exists($lockKey) > 0;
        } catch (\Throwable $e) {
            $this->logger->error('Error checking lock status', [
                'resource_key' => $resourceKey,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Generate a unique lock value.
     *
     * @return string
     */
    private function generateLockValue(): string
    {
        return uniqid('lock_', true) . ':' . bin2hex(random_bytes(8));
    }

    /**
     * Execute a callback while holding a distributed lock.
     *
     * @param  string  $resourceKey The unique key for the resource
     * @param  callable  $callback The callback to execute
     * @param  int  $ttl Lock TTL in seconds
     * @param  int  $retryDelay Delay between retries in milliseconds
     * @param  int  $maxRetries Maximum number of retries
     * @return mixed The result of the callback
     *
     * @throws \RuntimeException If lock cannot be acquired after max retries
     */
    public function withLock(
        string $resourceKey,
        callable $callback,
        int $ttl = self::DEFAULT_TTL,
        int $retryDelay = 100,
        int $maxRetries = 10,
    ): mixed {
        $lockValue = $this->generateLockValue();
        $attempts = 0;

        while ($attempts < $maxRetries) {
            if ($this->acquireLock($resourceKey, $ttl, $lockValue)) {
                try {
                    return $callback();
                } finally {
                    $this->releaseLock($resourceKey, $lockValue);
                }
            }

            $attempts++;
            if ($attempts < $maxRetries) {
                usleep($retryDelay * 1000); // Convert to microseconds
            }
        }

        throw new \RuntimeException(
            sprintf(
                'Failed to acquire lock after %d attempts for resource: %s',
                $maxRetries,
                $resourceKey
            )
        );
    }
}
