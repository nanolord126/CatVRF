<?php

declare(strict_types=1);

namespace Modules\Fashion\Listeners;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Contracts\Cache\Repository;
use Carbon\Carbon;

final class UpdateProductAnalyticsOnView
{
    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly Repository $cache,
    ) {}

    public function handle(string $productId, int $tenantId): void
    {
        $cacheKey = "fashion_product_views:{$tenantId}:{$productId}";

        // Increment view counter in cache with expiry
        $views = $this->cache->increment($cacheKey, 1, Carbon::now()->addHours(24));

        // Every 100 views, persist to database
        if ($views % 100 === 0) {
            $this->db->table('fashion_product_analytics')
                ->where('fashion_product_id', $productId)
                ->where('tenant_id', $tenantId)
                ->increment('views', $views);

            // Reset cache counter
            $this->cache->forget($cacheKey);
        }
    }
}
