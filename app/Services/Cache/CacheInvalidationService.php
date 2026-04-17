<?php declare(strict_types=1);

namespace App\Services\Cache;

use Illuminate\Contracts\Cache\Repository;
use Psr\Log\LoggerInterface;

final class CacheInvalidationService
{
    public function __construct(
        private readonly Repository $cache,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Invalidate cache by pattern.
     *
     * @param string $pattern Cache key pattern (supports wildcards)
     */
    public function invalidateByPattern(string $pattern): void
    {
        $this->logger->info('Invalidating cache by pattern', ['pattern' => $pattern]);
        
        // For Redis, we can use SCAN and DEL
        if ($this->isRedisDriver()) {
            $this->invalidateRedisByPattern($pattern);
            return;
        }

        // For other drivers, we need to track keys separately
        $this->logger->warning('Pattern-based cache invalidation not supported for current driver');
    }

    /**
     * Invalidate cache by key prefix.
     *
     * @param string $prefix Cache key prefix
     */
    public function invalidateByPrefix(string $prefix): void
    {
        $this->logger->info('Invalidating cache by prefix', ['prefix' => $prefix]);
        
        if ($this->isRedisDriver()) {
            $this->invalidateRedisByPattern($prefix . '*');
            return;
        }

        // For other drivers, iterate through tracked keys
        $this->invalidateTrackedKeysByPrefix($prefix);
    }

    /**
     * Invalidate specific cache keys.
     *
     * @param array $keys Array of cache keys to invalidate
     */
    public function invalidateKeys(array $keys): void
    {
        foreach ($keys as $key) {
            $this->cache->forget($key);
            $this->logger->debug('Cache key invalidated', ['key' => $key]);
        }
        
        $this->logger->info('Cache keys invalidated', ['count' => count($keys)]);
    }

    /**
     * Invalidate user-specific cache.
     *
     * @param int $userId User ID
     * @param string $prefix Cache key prefix (optional)
     */
    public function invalidateUserCache(int $userId, string $prefix = ''): void
    {
        $pattern = $prefix . "*:{$userId}:*";
        $this->invalidateByPattern($pattern);
    }

    /**
     * Invalidate cache for a specific entity.
     *
     * @param string $entityType Entity type (e.g., 'doctor', 'appointment')
     * @param int $entityId Entity ID
     */
    public function invalidateEntityCache(string $entityType, int $entityId): void
    {
        $pattern = "*:{$entityType}:{$entityId}:*";
        $this->invalidateByPattern($pattern);
    }

    /**
     * Tag-based cache invalidation (if supported by cache driver).
     *
     * @param array $tags Cache tags
     */
    public function invalidateByTags(array $tags): void
    {
        try {
            $this->cache->tags($tags)->flush();
            $this->logger->info('Cache invalidated by tags', ['tags' => $tags]);
        } catch (\Throwable $e) {
            $this->logger->warning('Tag-based cache invalidation not supported', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Track a cache key for later invalidation.
     *
     * @param string $key Cache key
     * @param string $prefix Prefix for grouping
     */
    public function trackKey(string $key, string $prefix): void
    {
        $trackingKey = "cache:tracking:{$prefix}";
        $trackedKeys = $this->cache->get($trackingKey, []);
        
        if (!in_array($key, $trackedKeys, true)) {
            $trackedKeys[] = $key;
            $this->cache->put($trackingKey, $trackedKeys, 86400); // Track for 24 hours
        }
    }

    private function isRedisDriver(): bool
    {
        return $this->cache->getStore() instanceof \Illuminate\Cache\RedisStore;
    }

    private function invalidateRedisByPattern(string $pattern): void
    {
        try {
            $redis = $this->cache->getStore()->connection();
            $iterator = null;
            $count = 0;
            
            while ($keys = $redis->scan($iterator, $pattern, 100)) {
                if (!empty($keys)) {
                    $redis->del(...$keys);
                    $count += count($keys);
                }
            }
            
            $this->logger->info('Redis cache invalidated by pattern', [
                'pattern' => $pattern,
                'count' => $count
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to invalidate Redis cache by pattern', [
                'pattern' => $pattern,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function invalidateTrackedKeysByPrefix(string $prefix): void
    {
        $trackingKey = "cache:tracking:{$prefix}";
        $trackedKeys = $this->cache->get($trackingKey, []);
        
        if (!empty($trackedKeys)) {
            $this->invalidateKeys($trackedKeys);
            $this->cache->forget($trackingKey);
        }
    }
}
