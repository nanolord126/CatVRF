<?php

declare(strict_types=1);

namespace App\Traits;

use TenantCacheService;

use App\Services\Tenancy\TenantCacheService;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Cache\Repository;

/**
 * Tenant Cacheable Trait
 *
 * Production 2026 CANON - Enforces Tenant-Aware Cache Operations
 *
 * Use this trait in services that need cache operations to ensure
 * all cache keys are automatically prefixed with tenant:{id}:.
 *
 * Usage:
 *   class MyService {
 *       use TenantCacheable;
 *
 *       public function getData(int $tenantId) {
 *           return $this->tenantCache()->remember($tenantId, 'my_key', 3600, fn() => ...);
 *       }
 *   }
 *
 * @author CatVRF Team
 *
 * @version 2026.04.17
 */
trait TenantCacheable
{
    private readonly TenantCacheService $tenantCacheService;

    /**
     * Get tenant cache service instance
     */
    protected function tenantCache(): TenantCacheService
    {
        return $this->tenantCacheService;
    }

    /**
     * Get current tenant ID from context
     */
    protected function currentTenantId(): ?int
    {
        return request()->attributes->get('tenant_id');
    }

    /**
     * Get cache key with automatic tenant prefix
     */
    protected function cacheKey(string $key, ?int $tenantId = null): string
    {
        $tenantId = $tenantId ?? $this->currentTenantId();

        return $this->tenantCache()->getPrefixedKey($tenantId, $key);
    }

    /**
     * Get item from cache with automatic tenant prefix
     */
    protected function cacheGet(string $key, mixed $default = null, ?int $tenantId = null): mixed
    {
        $tenantId = $tenantId ?? $this->currentTenantId();

        return $this->tenantCache()->get($tenantId, $key, $default);
    }

    /**
     * Put item in cache with automatic tenant prefix
     */
    protected function cachePut(string $key, mixed $value, int|\DateTimeInterface|null $ttl = null, ?int $tenantId = null): bool
    {
        $tenantId = $tenantId ?? $this->currentTenantId();

        return $this->tenantCache()->put($tenantId, $key, $value, $ttl);
    }

    /**
     * Remember value in cache with automatic tenant prefix
     */
    protected function cacheRemember(string $key, \Closure|\DateTimeInterface|int|null $ttl, \Closure $callback, ?int $tenantId = null): mixed
    {
        $tenantId = $tenantId ?? $this->currentTenantId();

        return $this->tenantCache()->remember($tenantId, $key, $ttl, $callback);
    }

    /**
     * Forget item from cache with automatic tenant prefix
     */
    protected function cacheForget(string $key, ?int $tenantId = null): bool
    {
        $tenantId = $tenantId ?? $this->currentTenantId();

        return $this->tenantCache()->forget($tenantId, $key);
    }

    /**
     * Get cache tags with automatic tenant prefix
     */
    protected function cacheTags(array $tags, ?int $tenantId = null): array
    {
        $tenantId = $tenantId ?? $this->currentTenantId();

        return $this->tenantCache()->getTags($tenantId, $tags);
    }

    /**
     * Get tagged cache repository with automatic tenant prefix
     */
    protected function cacheTagged(array $tags, ?int $tenantId = null): Repository
    {
        $tenantId = $tenantId ?? $this->currentTenantId();

        return $this->tenantCache()->tags($tenantId, $tags);
    }
}
