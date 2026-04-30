<?php

declare(strict_types=1);

namespace Modules\Fashion\Services;

use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Contracts\Cache\Repository;
use Carbon\Carbon;

final readonly class FashionAnalyticsService
{
    use WithAuditLogging;

    private const CACHE_TTL = 3600;

    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly Repository $cache,
        private readonly AuditService $auditService,
    ) {}

    public function getStoreAnalytics(int $storeId, int $tenantId): array
    {
        $cacheKey = "fashion_analytics_store:{$tenantId}:{$storeId}";

        return $this->cache->remember($cacheKey, Carbon::now()->addSeconds(self::CACHE_TTL), function () use ($storeId, $tenantId) {
            $orders = $this->db->table('fashion_orders')
                ->where('fashion_store_id', $storeId)
                ->where('tenant_id', $tenantId)
                ->where('status', 'completed')
                ->get();

            $totalRevenue = $orders->sum('total_amount');
            $totalOrders = $orders->count();
            $avgOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

            $topProducts = $this->db->table('fashion_order_items')
                ->join('fashion_products', 'fashion_order_items.fashion_product_id', '=', 'fashion_products.id')
                ->select('fashion_products.name', $this->db->raw('COUNT(*) as sold_count'), $this->db->raw('SUM(fashion_order_items.quantity) as total_quantity'))
                ->whereIn('fashion_order_items.fashion_order_id', $orders->pluck('id'))
                ->groupBy('fashion_products.id', 'fashion_products.name')
                ->orderByDesc('total_quantity')
                ->limit(10)
                ->get();

            return [
                'total_revenue' => $totalRevenue,
                'total_orders' => $totalOrders,
                'avg_order_value' => $avgOrderValue,
                'top_products' => $topProducts,
                'period' => 'all_time',
            ];
        });
    }

    public function getProductAnalytics(int $productId, int $tenantId): array
    {
        $cacheKey = "fashion_analytics_product:{$tenantId}:{$productId}";

        return $this->cache->remember($cacheKey, Carbon::now()->addSeconds(self::CACHE_TTL), function () use ($productId, $tenantId) {
            $views = $this->db->table('fashion_product_analytics')
                ->where('fashion_product_id', $productId)
                ->where('tenant_id', $tenantId)
                ->sum('views');

            $addToCart = $this->db->table('fashion_product_analytics')
                ->where('fashion_product_id', $productId)
                ->where('tenant_id', $tenantId)
                ->sum('add_to_cart');

            $purchases = $this->db->table('fashion_order_items')
                ->where('fashion_product_id', $productId)
                ->join('fashion_orders', 'fashion_order_items.fashion_order_id', '=', 'fashion_orders.id')
                ->where('fashion_orders.tenant_id', $tenantId)
                ->where('fashion_orders.status', 'completed')
                ->sum('fashion_order_items.quantity');

            $conversionRate = $addToCart > 0 ? ($purchases / $addToCart) * 100 : 0;

            return [
                'views' => $views,
                'add_to_cart' => $addToCart,
                'purchases' => $purchases,
                'conversion_rate' => round($conversionRate, 2),
            ];
        });
    }
}
