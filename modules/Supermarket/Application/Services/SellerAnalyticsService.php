<?php

declare(strict_types=1);

namespace Modules\Supermarket\Application\Services;

use App\Traits\WithAuditLogging;
use App\Traits\WithTelemetry;
use App\Services\FraudControlService;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

final class SellerAnalyticsService
{
    use WithAuditLogging;
    use WithTelemetry;

    private readonly FraudControlService $fraudControl;

    public function __construct(FraudControlService $fraudControl)
    {
        $this->fraudControl = $fraudControl;
    }

    public function getDashboard(Tenant $seller, string $period = '30d'): array
    {
        return $this->withSpan(
            'seller_analytics.dashboard',
            function () use ($seller, $period) {
                // Fraud check for analytics access
                $this->fraudControl->check([
                    'operation_type' => 'seller_analytics_dashboard',
                    'vertical' => 'supermarket',
                    'user_id' => $seller->owner_id ?? null,
                    'tenant_id' => $seller->id,
                    'correlation_id' => $this->generateCorrelationId(),
                ]);
                $from = $this->getPeriodStart($period);

                // Check cache first
        $cacheKey = "supermarket:analytics:{$seller->id}:{$period}";
        $cached = \Illuminate\Support\Facades\Cache::tags(['supermarket', 'analytics', "seller:{$seller->id}"])->get($cacheKey);
        
        if ($cached !== null) {
            return $cached;
        }

        $result = [
            'period' => $period,
            'from' => $from->toIso8601String(),
            'to' => now()->toIso8601String(),
            
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
            'active_subscriptions' => $this->activeSubscriptions($seller),
            'subscription_revenue' => $this->subscriptionRevenue($seller, $from),
        ];

        // Cache for 5 minutes with tags
        \Illuminate\Support\Facades\Cache::tags(['supermarket', 'analytics', "seller:{$seller->id}"])
            ->put($cacheKey, $result, now()->addMinutes(5));

        return $result;
            },
            $this->getStandardAttributes(
                vertical: 'supermarket',
                operation: 'seller_analytics_dashboard',
                userId: (string) $seller->owner_id ?? null,
                tenantId: (string) $seller->id,
            ),
        );
    }

    private function getPeriodStart(string $period): Carbon
    {
        return match ($period) {
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            '90d' => now()->subDays(90),
            '1m' => now()->subMonth(),
            '3m' => now()->subMonths(3),
            '6m' => now()->subMonths(6),
            '1y' => now()->subYear(),
            default => now()->subDays(30),
        };
    }

    private function revenue(Tenant $seller, Carbon $from): float
    {
        return (float) DB::table('supermarket_orders')
            ->where('seller_id', $seller->id)
            ->where('status', 'delivered')
            ->where('created_at', '>=', $from)
            ->sum('total_amount');
    }

    private function ordersCount(Tenant $seller, Carbon $from): int
    {
        return DB::table('supermarket_orders')
            ->where('seller_id', $seller->id)
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
        $orders = $this->ordersCount($seller, $from);
        $views = DB::table('product_views')
            ->join('products', 'products.id', '=', 'product_views.product_id')
            ->where('products.tenant_id', $seller->id)
            ->where('product_views.created_at', '>=', $from)
            ->count();

        return $views > 0 ? round(($orders / $views) * 100, 2) : 0;
    }

    private function subVerticalBreakdown(Tenant $seller, Carbon $from): array
    {
        return DB::table('supermarket_orders')
            ->where('seller_id', $seller->id)
            ->where('created_at', '>=', $from)
            ->select('sub_vertical', DB::raw('SUM(total_amount) as revenue'), DB::raw('COUNT(*) as orders'))
            ->groupBy('sub_vertical')
            ->orderByDesc('revenue')
            ->get()
            ->toArray();
    }

    private function topProducts(Tenant $seller, Carbon $from, int $limit = 10): array
    {
        return DB::table('order_items')
            ->join('supermarket_orders', 'supermarket_orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->where('supermarket_orders.seller_id', $seller->id)
            ->where('supermarket_orders.created_at', '>=', $from)
            ->select(
                'order_items.product_id',
                'products.name',
                DB::raw('SUM(order_items.quantity) as total_qty'),
                DB::raw('SUM(order_items.total_price) as revenue'),
            )
            ->groupBy('order_items.product_id', 'products.name')
            ->orderByDesc('revenue')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    private function b2bVsB2c(Tenant $seller, Carbon $from): array
    {
        $b2bRevenue = DB::table('supermarket_orders')
            ->where('seller_id', $seller->id)
            ->where('is_b2b', true)
            ->where('created_at', '>=', $from)
            ->sum('total_amount');

        $b2cRevenue = DB::table('supermarket_orders')
            ->where('seller_id', $seller->id)
            ->where('is_b2b', false)
            ->where('created_at', '>=', $from)
            ->sum('total_amount');

        $total = $b2bRevenue + $b2cRevenue;

        return [
            'b2b_revenue' => (float) $b2bRevenue,
            'b2c_revenue' => (float) $b2cRevenue,
            'b2b_share' => $total > 0 ? round(($b2bRevenue / $total) * 100, 2) : 0,
            'b2c_share' => $total > 0 ? round(($b2cRevenue / $total) * 100, 2) : 0,
        ];
    }

    private function returnsRate(Tenant $seller, Carbon $from): float
    {
        $totalOrders = $this->ordersCount($seller, $from);
        $returnsCount = DB::table('supermarket_returns')
            ->where('seller_id', $seller->id)
            ->where('created_at', '>=', $from)
            ->count();

        return $totalOrders > 0 ? round(($returnsCount / $totalOrders) * 100, 2) : 0;
    }

    private function cancelledRate(Tenant $seller, Carbon $from): float
    {
        $totalOrders = DB::table('supermarket_orders')
            ->where('seller_id', $seller->id)
            ->where('created_at', '>=', $from)
            ->count();

        $cancelledCount = DB::table('supermarket_orders')
            ->where('seller_id', $seller->id)
            ->where('status', 'cancelled')
            ->where('created_at', '>=', $from)
            ->count();

        return $totalOrders > 0 ? round(($cancelledCount / $totalOrders) * 100, 2) : 0;
    }

    private function revenueTrend(Tenant $seller, Carbon $from): array
    {
        $previousFrom = $from->copy()->subDays($from->diffInDays(now()));
        
        $currentRevenue = $this->revenue($seller, $from);
        $previousRevenue = $this->revenue($seller, $previousFrom);

        $growth = $previousRevenue > 0 
            ? (($currentRevenue - $previousRevenue) / $previousRevenue) * 100 
            : 0;

        return [
            'current' => $currentRevenue,
            'previous' => $previousRevenue,
            'growth_percent' => round($growth, 2),
        ];
    }

    private function ordersTrend(Tenant $seller, Carbon $from): array
    {
        $previousFrom = $from->copy()->subDays($from->diffInDays(now()));
        
        $currentOrders = $this->ordersCount($seller, $from);
        $previousOrders = DB::table('supermarket_orders')
            ->where('seller_id', $seller->id)
            ->where('created_at', '>=', $previousFrom)
            ->where('created_at', '<', $from)
            ->count();

        $growth = $previousOrders > 0 
            ? (($currentOrders - $previousOrders) / $previousOrders) * 100 
            : 0;

        return [
            'current' => $currentOrders,
            'previous' => $previousOrders,
            'growth_percent' => round($growth, 2),
        ];
    }

    private function activeSubscriptions(Tenant $seller): int
    {
        return DB::table('supermarket_subscriptions')
            ->where('seller_id', $seller->id)
            ->where('status', 'active')
            ->count();
    }

    private function subscriptionRevenue(Tenant $seller, Carbon $from): float
    {
        return (float) DB::table('supermarket_orders')
            ->where('seller_id', $seller->id)
            ->where('is_subscription', true)
            ->where('created_at', '>=', $from)
            ->sum('total_amount');
    }
}
