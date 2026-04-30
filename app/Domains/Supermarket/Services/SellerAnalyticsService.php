<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Services;

use App\Domains\Supermarket\Models\SupermarketOrder;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final readonly class SellerAnalyticsService
{
    public function getDashboard(Tenant $seller, string $period = '30d'): array
    {
        $from = match ($period) {
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            '90d' => now()->subDays(90),
            default => now()->subDays(30),
        };

        return [
            'period' => $period,
            'total_revenue' => $this->revenue($seller, $from),
            'orders_count' => $this->ordersCount($seller, $from),
            'avg_order_value' => $this->aov($seller, $from),
            'conversion_rate' => $this->conversionRate($seller, $from),
            'sub_vertical_stats' => $this->subVerticalBreakdown($seller, $from),
            'top_products' => $this->topProducts($seller, $from, 10),
            'b2b_vs_b2c' => $this->b2bVsB2c($seller, $from),
            'returns_rate' => $this->returnsRate($seller, $from),
            'cancelled_rate' => $this->cancelledRate($seller, $from),
            'revenue_trend' => $this->revenueTrend($seller, $from),
            'orders_trend' => $this->ordersTrend($seller, $from),
        ];
    }

    private function revenue(Tenant $seller, Carbon $from): float
    {
        return (float) SupermarketOrder::where('seller_id', $seller->id)
            ->where('status', 'delivered')
            ->where('created_at', '>=', $from)
            ->sum('total_amount');
    }

    private function ordersCount(Tenant $seller, Carbon $from): int
    {
        return SupermarketOrder::where('seller_id', $seller->id)
            ->where('status', 'delivered')
            ->where('created_at', '>=', $from)
            ->count();
    }

    private function aov(Tenant $seller, Carbon $from): float
    {
        $revenue = $this->revenue($seller, $from);
        $count = $this->ordersCount($seller, $from);

        return $count > 0 ? $revenue / $count : 0;
    }

    private function conversionRate(Tenant $seller, Carbon $from): float
    {
        // Placeholder - implement actual conversion calculation
        return 0;
    }

    private function subVerticalBreakdown(Tenant $seller, Carbon $from): array
    {
        return DB::table('supermarket_orders')
            ->where('seller_id', $seller->id)
            ->where('created_at', '>=', $from)
            ->select('sub_vertical', DB::raw('SUM(total_amount) as revenue'), DB::raw('COUNT(*) as orders'))
            ->groupBy('sub_vertical')
            ->get()
            ->toArray();
    }

    private function topProducts(Tenant $seller, Carbon $from, int $limit = 10): array
    {
        return DB::table('order_items')
            ->join('supermarket_orders', 'supermarket_orders.id', '=', 'order_items.order_id')
            ->where('supermarket_orders.seller_id', $seller->id)
            ->where('supermarket_orders.created_at', '>=', $from)
            ->select(
                'order_items.product_id',
                DB::raw('SUM(order_items.quantity) as total_qty'),
                DB::raw('SUM(order_items.total_price) as revenue'),
            )
            ->groupBy('order_items.product_id')
            ->orderBy('revenue', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    private function b2bVsB2c(Tenant $seller, Carbon $from): array
    {
        $b2bRevenue = (float) SupermarketOrder::where('seller_id', $seller->id)
            ->where('is_b2b', true)
            ->where('status', 'delivered')
            ->where('created_at', '>=', $from)
            ->sum('total_amount');

        $b2cRevenue = (float) SupermarketOrder::where('seller_id', $seller->id)
            ->where('is_b2b', false)
            ->where('status', 'delivered')
            ->where('created_at', '>=', $from)
            ->sum('total_amount');

        $total = $b2bRevenue + $b2cRevenue;

        return [
            'b2b_revenue' => $b2bRevenue,
            'b2c_revenue' => $b2cRevenue,
            'b2b_share' => $total > 0 ? ($b2bRevenue / $total) * 100 : 0,
            'b2c_share' => $total > 0 ? ($b2cRevenue / $total) * 100 : 0,
        ];
    }

    private function returnsRate(Tenant $seller, Carbon $from): float
    {
        // Placeholder - implement actual returns rate calculation
        return 0;
    }

    private function cancelledRate(Tenant $seller, Carbon $from): float
    {
        $totalOrders = SupermarketOrder::where('seller_id', $seller->id)
            ->where('created_at', '>=', $from)
            ->count();

        if ($totalOrders === 0) {
            return 0;
        }

        $cancelledOrders = SupermarketOrder::where('seller_id', $seller->id)
            ->where('status', 'cancelled')
            ->where('created_at', '>=', $from)
            ->count();

        return ($cancelledOrders / $totalOrders) * 100;
    }

    private function revenueTrend(Tenant $seller, Carbon $from): array
    {
        // Placeholder - implement daily revenue trend
        return [];
    }

    private function ordersTrend(Tenant $seller, Carbon $from): array
    {
        // Placeholder - implement daily orders trend
        return [];
    }
}
