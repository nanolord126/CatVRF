<?php declare(strict_types=1);

namespace App\Traits;

use App\Services\Tenancy\TenantCacheService;
use Illuminate\Support\Facades\App;

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
 * @version 2026.04.17
 */
trait TenantCacheable
{
    /**
     * Get tenant cache service instance
     *
     * @return TenantCacheService
     */
    protected function tenantCache(): TenantCacheService
    {
        return App::make(TenantCacheService::class);
    }

    /**
     * Get current tenant ID from context
     *
     * @return int|null
     */
    protected function currentTenantId(): ?int
    {
        return request()->attributes->get('tenant_id');
    }

    /**
     * Get cache key with automatic tenant prefix
     *
     * @param string $key
     * @param int|null $tenantId
     * @return string
     */
    protected function cacheKey(string $key, ?int $tenantId = null): string
    {
        $tenantId = $tenantId ?? $this->currentTenantId();
        return $this->tenantCache()->getPrefixedKey($tenantId, $key);
    }

    /**
     * Get item from cache with automatic tenant prefix
     *
     * @param string $key
     * @param mixed $default
     * @param int|null $tenantId
     * @return mixed
     */
    protected function cacheGet(string $key, mixed $default = null, ?int $tenantId = null): mixed
    {
        $tenantId = $tenantId ?? $this->currentTenantId();
        return $this->tenantCache()->get($tenantId, $key, $default);
    }

    /**
     * Put item in cache with automatic tenant prefix
     *
     * @param string $key
     * @param mixed $value
     * @param int|\DateTimeInterface|null $ttl
     * @param int|null $tenantId
     * @return bool
     */
    protected function cachePut(string $key, mixed $value, int|\DateTimeInterface|null $ttl = null, ?int $tenantId = null): bool
    {
        $tenantId = $tenantId ?? $this->currentTenantId();
        return $this->tenantCache()->put($tenantId, $key, $value, $ttl);
    }

    /**
     * Remember value in cache with automatic tenant prefix
     *
     * @param string $key
     * @param \Closure|\DateTimeInterface|int|null $ttl
     * @param \Closure $callback
     * @param int|null $tenantId
     * @return mixed
     */
    protected function cacheRemember(string $key, \Closure|\DateTimeInterface|int|null $ttl, \Closure $callback, ?int $tenantId = null): mixed
    {
        $tenantId = $tenantId ?? $this->currentTenantId();
        return $this->tenantCache()->remember($tenantId, $key, $ttl, $callback);
    }

    /**
     * Forget item from cache with automatic tenant prefix
     *
     * @param string $key
     * @param int|null $tenantId
     * @return bool
     */
    protected function cacheForget(string $key, ?int $tenantId = null): bool
    {
        $tenantId = $tenantId ?? $this->currentTenantId();
        return $this->tenantCache()->forget($tenantId, $key);
    }

    /**
     * Get cache tags with automatic tenant prefix
     *
     * @param array $tags
     * @param int|null $tenantId
     * @return array
     */
    protected function cacheTags(array $tags, ?int $tenantId = null): array
    {
        $tenantId = $tenantId ?? $this->currentTenantId();
        return $this->tenantCache()->getTags($tenantId, $tags);
    }

    /**
     * Get tagged cache repository with automatic tenant prefix
     *
     * @param array $tags
     * @param int|null $tenantId
     * @return \Illuminate\Contracts\Cache\Repository
     */
    protected function cacheTagged(array $tags, ?int $tenantId = null): \Illuminate\Contracts\Cache\Repository
    {
        $tenantId = $tenantId ?? $this->currentTenantId();
        return $this->tenantCache()->tags($tenantId, $tags);
    }
}
