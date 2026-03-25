declare(strict_types=1);

<?php
declare(strict_types=1);

namespace App\Services\Security;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

final /**
 * TenantAwareRateLimiter
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class TenantAwareRateLimiter
{
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
