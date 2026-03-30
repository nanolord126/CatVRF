<?php declare(strict_types=1);

namespace App\Services\Security;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class TenantAwareRateLimiter extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    // Dependencies injected via constructor
        // Add private readonly properties here
        public function check(int $tenantId, string $key, int $limit, int $window = 60): bool
        {
            $cacheKey = "rate_limit:{$tenantId}:{$key}";
            $count = (int)Cache::get($cacheKey, 0);

            if ($count >= $limit) {
                return false;
            }

            Cache::increment($cacheKey);
            Cache::put($cacheKey, $count + 1, $window);

            return true;
        }

        public function remaining(int $tenantId, string $key, int $limit): int
        {
            $cacheKey = "rate_limit:{$tenantId}:{$key}";
            $count = (int)Cache::get($cacheKey, 0);
            return max(0, $limit - $count);
        }

        public function reset(int $tenantId, string $key): void
        {
            Cache::forget("rate_limit:{$tenantId}:{$key}");
        }
}
