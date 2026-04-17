<?php declare(strict_types=1);

namespace Modules\Fashion\Listeners;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

final class UpdateProductAnalyticsOnView
{
    public function handle(string $productId, int $tenantId): void
    {
        $cacheKey = "fashion_product_views:{$tenantId}:{$productId}";
        
        // Increment view counter in cache with expiry
        $views = Cache::increment($cacheKey, 1, Carbon::now()->addHours(24));
        
        // Every 100 views, persist to database
        if ($views % 100 === 0) {
            DB::table('fashion_product_analytics')
                ->where('fashion_product_id', $productId)
                ->where('tenant_id', $tenantId)
                ->increment('views', $views);
            
            // Reset cache counter
            Cache::forget($cacheKey);
        }
    }
}
