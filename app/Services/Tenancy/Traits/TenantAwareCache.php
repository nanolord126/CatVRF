<?php declare(strict_types=1);

namespace App\Services\Tenancy\Traits;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

/**
 * Tenant Aware Cache Trait
 * 
 * Automatically prefixes cache keys with tenant:{tenant_id}:
 * to prevent cache collisions between tenants
 * 
 * Usage:
 * class MyService {
 *     use TenantAwareCache;
 *     
 *     public function myMethod() {
 *         $key = $this->tenantCacheKey('my_key');
 *         Cache::put($key, $value, 3600);
 *     }
 * }
 */
trait TenantAwareCache
{
    /**
     * Get tenant-prefixed cache key
     */
    protected function tenantCacheKey(string $key): string
    {
        $tenantId = $this->getTenantId();

        if ($tenantId) {
            return "tenant:{$tenantId}:{$key}";
        }

        return "global:{$key}";
    }

    /**
     * Get tenant-prefixed Redis key
     */
    protected function tenantRedisKey(string $key): string
    {
        return $this->tenantCacheKey($key);
    }

    /**
     * Cache data with tenant prefix
     */
    protected function tenantCache(string $key, $value, int $ttl = null): bool
    {
        return Cache::put($this->tenantCacheKey($key), $value, $ttl ?? 3600);
    }

    /**
     * Get cached data with tenant prefix
     */
    protected function tenantCacheGet(string $key, mixed $default = null): mixed
    {
        return Cache::get($this->tenantCacheKey($key), $default);
    }

    /**
     * Forget cached data with tenant prefix
     */
    protected function tenantCacheForget(string $key): bool
    {
        return Cache::forget($this->tenantCacheKey($key));
    }

    /**
     * Cache with tags (tenant-aware)
     */
    protected function tenantCacheWithTags(string $key, $value, array $tags, int $ttl = null): bool
    {
        $tenantId = $this->getTenantId();
        $prefixedTags = $tenantId ? array_map(fn($tag) => "tenant:{$tenantId}:{$tag}", $tags) : $tags;

        return Cache::tags($prefixedTags)->put($this->tenantCacheKey($key), $value, $ttl ?? 3600);
    }

    /**
     * Redis operation with tenant prefix
     */
    protected function tenantRedisSet(string $key, $value, int $ttl = null): bool
    {
        $redisKey = $this->tenantRedisKey($key);

        if ($ttl) {
            return (bool) Redis::setex($redisKey, $ttl, $value);
        }

        return (bool) Redis::set($redisKey, $value);
    }

    /**
     * Redis get with tenant prefix
     */
    protected function tenantRedisGet(string $key): mixed
    {
        return Redis::get($this->tenantRedisKey($key));
    }

    /**
     * Redis delete with tenant prefix
     */
    protected function tenantRedisDel(string $key): int
    {
        return Redis::del($this->tenantRedisKey($key));
    }

    /**
     * Get current tenant ID
     */
    protected function getTenantId(): ?int
    {
        // Try multiple methods to get tenant ID
        if (function_exists('tenant') && tenant('id')) {
            return (int) tenant('id');
        }

        if (request()->hasHeader('X-Tenant-ID')) {
            return (int) request()->header('X-Tenant-ID');
        }

        if (request()->attributes->has('tenant_id')) {
            return (int) request()->attributes->get('tenant_id');
        }

        if (auth()->check() && auth()->user()->tenant_id) {
            return (int) auth()->user()->tenant_id;
        }

        return null;
    }

    /**
     * Flush all cache for current tenant
     */
    protected function flushTenantCache(): bool
    {
        $tenantId = $this->getTenantId();

        if (!$tenantId) {
            return false;
        }

        // Use cache tags if available
        Cache::tags(["tenant:{$tenantId}"])->flush();

        return true;
    }
}
