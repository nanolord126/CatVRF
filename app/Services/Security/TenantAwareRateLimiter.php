<?php declare(strict_types=1);

namespace App\Services\Security;



use Illuminate\Support\Str;
use Illuminate\Cache\CacheManager;

/**
 * Class TenantAwareRateLimiter
 *
 * Component of the CatVRF platform.
 * Follows strict coding standards:
 * - final class (no inheritance unless required)
 * - private readonly properties
 * - Constructor injection only
 * - correlation_id in all operations
 *
 * @package App\Services\Security
 */
final readonly class TenantAwareRateLimiter
{
    public function __construct(
        private readonly CacheManager $cache,
    ) {}


    // Dependencies injected via constructor
        // Add private readonly properties here
        public function check(int $tenantId, string $key, int $limit, int $window = 60): bool
        {
            $cacheKey = "rate_limit:{$tenantId}:{$key}";
            $count = (int)$this->cache->get($cacheKey, 0);

            if ($count >= $limit) {
                return false;
            }

            $this->cache->increment($cacheKey);
            $this->cache->put($cacheKey, $count + 1, $window);

            return true;
        }

        /**
         * Handle remaining operation.
         *
         * @throws \DomainException
         */
        public function remaining(int $tenantId, string $key, int $limit): int
        {
            $cacheKey = "rate_limit:{$tenantId}:{$key}";
            $count = (int)$this->cache->get($cacheKey, 0);
            return max(0, $limit - $count);
        }

        public function reset(int $tenantId, string $key): void
        {
            $this->cache->forget("rate_limit:{$tenantId}:{$key}");
        }
}
