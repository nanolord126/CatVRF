<?php declare(strict_types=1);

namespace App\Services\Tenancy;

use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Tenant Cache Service
 *
 * Production 2026 CANON - Tenant-Aware Cache Operations
 *
 * Enforces mandatory tenant:{id}: prefix for all cache/Redis operations
 * to prevent cache collisions between tenants. This is critical for:
 * - Medical records cache isolation
 * - Delivery tracking cache isolation
 * - AI constructor cache isolation
 * - Session cache isolation
 *
 * All cache operations MUST go through this service in tenant context.
 * Direct Cache:: calls are forbidden in production code.
 *
 * @author CatVRF Team
 * @version 2026.04.17
 */
final readonly class TenantCacheService
{
    private const CACHE_PREFIX = 'tenant:';

    public function __construct(
        private readonly CacheManager $cache,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Get tenant-prefixed cache key
     *
     * @param int|null $tenantId
     * @param string $key
     * @return string
     * @throws RuntimeException
     */
    public function getPrefixedKey(?int $tenantId, string $key): string
    {
        if ($tenantId === null) {
            throw new RuntimeException('Tenant ID is required for cache operations in tenant context');
        }

        return self::CACHE_PREFIX . $tenantId . ':' . $key;
    }

    /**
     * Get item from cache with tenant prefix
     *
     * @param int|null $tenantId
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(?int $tenantId, string $key, mixed $default = null): mixed
    {
        $prefixedKey = $this->getPrefixedKey($tenantId, $key);
        return $this->cache->get($prefixedKey, $default);
    }

    /**
     * Put item in cache with tenant prefix
     *
     * @param int|null $tenantId
     * @param string $key
     * @param mixed $value
     * @param int|\DateTimeInterface|null $ttl
     * @return bool
     */
    public function put(?int $tenantId, string $key, mixed $value, int|\DateTimeInterface|null $ttl = null): bool
    {
        $prefixedKey = $this->getPrefixedKey($tenantId, $key);
        
        $this->logger->debug('Cache put', [
            'tenant_id' => $tenantId,
            'key' => $prefixedKey,
            'ttl' => $ttl,
        ]);

        return $this->cache->put($prefixedKey, $value, $ttl);
    }

    /**
     * Remember value in cache with tenant prefix
     *
     * @param int|null $tenantId
     * @param string $key
     * @param \Closure|\DateTimeInterface|int|null $ttl
     * @param \Closure $callback
     * @return mixed
     */
    public function remember(?int $tenantId, string $key, \Closure|\DateTimeInterface|int|null $ttl, \Closure $callback): mixed
    {
        $prefixedKey = $this->getPrefixedKey($tenantId, $key);
        return $this->cache->remember($prefixedKey, $ttl, $callback);
    }

    /**
     * Remember value forever in cache with tenant prefix
     *
     * @param int|null $tenantId
     * @param string $key
     * @param \Closure $callback
     * @return mixed
     */
    public function rememberForever(?int $tenantId, string $key, \Closure $callback): mixed
    {
        $prefixedKey = $this->getPrefixedKey($tenantId, $key);
        return $this->cache->rememberForever($prefixedKey, $callback);
    }

    /**
     * Check if key exists in cache with tenant prefix
     *
     * @param int|null $tenantId
     * @param string $key
     * @return bool
     */
    public function has(?int $tenantId, string $key): bool
    {
        $prefixedKey = $this->getPrefixedKey($tenantId, $key);
        return $this->cache->has($prefixedKey);
    }

    /**
     * Forget item from cache with tenant prefix
     *
     * @param int|null $tenantId
     * @param string $key
     * @return bool
     */
    public function forget(?int $tenantId, string $key): bool
    {
        $prefixedKey = $this->getPrefixedKey($tenantId, $key);
        
        $this->logger->debug('Cache forget', [
            'tenant_id' => $tenantId,
            'key' => $prefixedKey,
        ]);

        return $this->cache->forget($prefixedKey);
    }

    /**
     * Flush cache for specific tenant
     *
     * @param int $tenantId
     * @return bool
     */
    public function flushTenant(int $tenantId): bool
    {
        $pattern = self::CACHE_PREFIX . $tenantId . ':*';
        
        $this->logger->info('Tenant cache flush', [
            'tenant_id' => $tenantId,
            'pattern' => $pattern,
        ]);

        // Note: This requires Redis implementation for pattern matching
        // For file/database cache, this will be a no-op
        if ($this->cache->getStore() instanceof \Illuminate\Cache\RedisStore) {
            $redis = $this->cache->getStore()->connection();
            $keys = $redis->keys($pattern);
            
            if (!empty($keys)) {
                $redis->del(...$keys);
                return true;
            }
        }

        return false;
    }

    /**
     * Get cache tags with tenant prefix
     *
     * @param int|null $tenantId
     * @param array $tags
     * @return array
     */
    public function getTags(?int $tenantId, array $tags): array
    {
        return array_map(fn(string $tag) => self::CACHE_PREFIX . $tenantId . ':' . $tag, $tags);
    }

    /**
     * Get tagged cache repository with tenant prefix
     *
     * @param int|null $tenantId
     * @param array $tags
     * @return Repository
     */
    public function tags(?int $tenantId, array $tags): Repository
    {
        $prefixedTags = $this->getTags($tenantId, $tags);
        return $this->cache->tags($prefixedTags);
    }

    /**
     * Increment value in cache with tenant prefix
     *
     * @param int|null $tenantId
     * @param string $key
     * @param int $value
     * @return int|bool
     */
    public function increment(?int $tenantId, string $key, int $value = 1): int|bool
    {
        $prefixedKey = $this->getPrefixedKey($tenantId, $key);
        return $this->cache->increment($prefixedKey, $value);
    }

    /**
     * Decrement value in cache with tenant prefix
     *
     * @param int|null $tenantId
     * @param string $key
     * @param int $value
     * @return int|bool
     */
    public function decrement(?int $tenantId, string $key, int $value = 1): int|bool
    {
        $prefixedKey = $this->getPrefixedKey($tenantId, $key);
        return $this->cache->decrement($prefixedKey, $value);
    }

    /**
     * Add item to cache if it doesn't exist (tenant-prefixed)
     *
     * @param int|null $tenantId
     * @param string $key
     * @param mixed $value
     * @param int|\DateTimeInterface|null $ttl
     * @return bool
     */
    public function add(?int $tenantId, string $key, mixed $value, int|\DateTimeInterface|null $ttl = null): bool
    {
        $prefixedKey = $this->getPrefixedKey($tenantId, $key);
        return $this->cache->add($prefixedKey, $value, $ttl);
    }

    /**
     * Get multiple items from cache with tenant prefix
     *
     * @param int|null $tenantId
     * @param array $keys
     * @return array
     */
    public function many(?int $tenantId, array $keys): array
    {
        $prefixedKeys = array_map(fn(string $key) => $this->getPrefixedKey($tenantId, $key), $keys);
        $results = $this->cache->many($prefixedKeys);
        
        // Map back to original keys
        $mapped = [];
        foreach ($keys as $key) {
            $prefixedKey = $this->getPrefixedKey($tenantId, $key);
            $mapped[$key] = $results[$prefixedKey] ?? null;
        }
        
        return $mapped;
    }

    /**
     * Put multiple items in cache with tenant prefix
     *
     * @param int|null $tenantId
     * @param array $values
     * @param int|\DateTimeInterface|null $ttl
     * @return bool
     */
    public function putMany(?int $tenantId, array $values, int|\DateTimeInterface|null $ttl = null): bool
    {
        $prefixedValues = [];
        foreach ($values as $key => $value) {
            $prefixedKey = $this->getPrefixedKey($tenantId, $key);
            $prefixedValues[$prefixedKey] = $value;
        }
        
        return $this->cache->putMany($prefixedValues, $ttl);
    }
}
