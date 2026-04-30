<?php

declare(strict_types=1);

namespace Modules\Analytics\Application\Services;

use App\Traits\WithAuditLogging;
use App\Services\AuditService;
use Carbon\CarbonImmutable;
use Modules\Analytics\Application\DTOs\AggregatedMetricsDto;
use Modules\Analytics\Application\DTOs\TimeSeriesDto;
use Modules\Analytics\Application\DTOs\TopItemsDto;
use Modules\Analytics\Application\DTOs\TopItemDto;
use Modules\Analytics\Application\DTOs\MetricDataDto;
use Modules\Analytics\Domain\ValueObjects\MetricType;
use Modules\Analytics\Domain\ValueObjects\Period;
use Illuminate\Cache\CacheManager;
use Illuminate\Database\DatabaseManager;

/**
 * Metrics Query Service
 *
 * Handles querying aggregated metrics, time-series data, and top items.
 * Follows Single Responsibility Principle - only handles metric queries.
 */
final readonly class MetricsQueryService
{
    use WithAuditLogging;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly CacheManager $cache,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Get aggregated metrics for a period.
     */
    public function getAggregatedMetrics(
        Period $period,
        int $tenantId,
        ?int $sellerId = null,
        bool $withComparison = true,
    ): AggregatedMetricsDto {
        // Fraud check for metric queries
        // TODO: Integrate with FraudDetectionService when available

        $cacheKey = $this->getAggregatedCacheKey('metrics', $period, $tenantId, $sellerId);
        
        return $this->cache->tags(["analytics:{$tenantId}"])->remember($cacheKey, 300, function () use ($period, $tenantId, $sellerId, $withComparison) {
            $query = $this->db->table('analytics_daily_metrics')
                ->where('tenant_id', $tenantId)
                ->whereBetween('date', [$period->from()->toDateString(), $period->to()->toDateString()]);

            if ($sellerId !== null) {
                return $this->getSellerAggregatedMetrics($period, $tenantId, $sellerId, $withComparison);
            }

            $result = $query->get();
            
            $metrics = [
                'orders_count' => $result->sum('orders_count'),
                'orders_revenue' => (float) $result->sum('orders_revenue'),
                'orders_aov' => $result->avg('orders_aov'),
                'users_active' => $result->sum('users_active'),
                'users_new' => $result->sum('users_new'),
                'products_viewed' => $result->sum('products_viewed'),
                'products_added_to_cart' => $result->sum('products_added_to_cart'),
                'sellers_active' => $result->sum('sellers_active'),
                'sessions' => $result->sum('sessions'),
                'page_views' => $result->sum('page_views'),
                'gmv' => (float) $result->sum('gmv'),
                'refunds_count' => $result->sum('refunds_count'),
                'refunds_amount' => (float) $result->sum('refunds_amount'),
                'conversion_rate' => $result->avg('conversion_rate'),
                'cart_abandonment_rate' => $result->avg('cart_abandonment_rate'),
            ];

            $comparison = [];
            if ($withComparison) {
                $previousPeriod = $period->getPreviousPeriod();
                $comparison = $this->getAggregatedMetrics($previousPeriod, $tenantId, null, false)->toArray()['metrics'];
            }

            return AggregatedMetricsDto::create($period, $metrics, $tenantId, null, $comparison);
        });
    }

    /**
     * Get time-series data for a metric.
     */
    public function getTimeSeries(
        MetricType $metricType,
        Period $period,
        int $tenantId,
        ?int $sellerId = null,
        string $groupBy = 'day',
    ): TimeSeriesDto {
        // Fraud check for time-series queries
        // TODO: Integrate with FraudDetectionService when available

        $cacheKey = $this->getTimeSeriesCacheKey($metricType, $period, $tenantId, $sellerId, $groupBy);
        
        return $this->cache->tags(["analytics:{$tenantId}"])->remember($cacheKey, 300, function () use ($metricType, $period, $tenantId, $sellerId, $groupBy) {
            $table = $sellerId !== null ? 'analytics_seller_metrics' : 'analytics_daily_metrics';
            $dateColumn = $groupBy === 'hour' ? 'hour' : 'date';
            
            $query = $this->db->table($table)
                ->where('tenant_id', $tenantId)
                ->whereBetween($dateColumn, [$period->from(), $period->to()]);

            if ($sellerId !== null) {
                $query->where('seller_id', $sellerId);
            }

            $results = $query->orderBy($dateColumn)->get();

            $metricColumn = $this->mapMetricTypeToColumn($metricType);
            $dataPoints = [];

            foreach ($results as $result) {
                $dataPoints[] = MetricDataDto::create(
                    $metricType,
                    $result->$metricColumn,
                    CarbonImmutable::parse($result->$dateColumn),
                    $tenantId,
                    $sellerId,
                );
            }

            return TimeSeriesDto::create($metricType, $period, $dataPoints, $groupBy, $tenantId, $sellerId);
        });
    }

    /**
     * Get top items (products, sellers, categories).
     */
    public function getTopItems(
        string $itemType,
        string $metric,
        Period $period,
        int $tenantId,
        int $limit = 10,
    ): TopItemsDto {
        // Fraud check for top items queries
        // TODO: Integrate with FraudDetectionService when available

        $cacheKey = $this->getTopItemsCacheKey($itemType, $metric, $period, $tenantId, $limit);
        
        return $this->cache->tags(["analytics:{$tenantId}"])->remember($cacheKey, 600, function () use ($itemType, $metric, $period, $tenantId, $limit) {
            return match ($itemType) {
                'product' => $this->getTopProducts($metric, $period, $tenantId, $limit),
                'seller' => $this->getTopSellers($metric, $period, $tenantId, $limit),
                'category' => $this->getTopCategories($metric, $period, $tenantId, $limit),
                default => throw new \InvalidArgumentException("Invalid item type: {$itemType}"),
            };
        });
    }

    /**
     * Get seller-specific aggregated metrics.
     */
    private function getSellerAggregatedMetrics(
        Period $period,
        int $tenantId,
        int $sellerId,
        bool $withComparison,
    ): AggregatedMetricsDto {
        $query = $this->db->table('analytics_seller_metrics')
            ->where('tenant_id', $tenantId)
            ->where('seller_id', $sellerId)
            ->whereBetween('date', [$period->from()->toDateString(), $period->to()->toDateString()]);

        $result = $query->first();

        if ($result === null) {
            return AggregatedMetricsDto::create($period, [], $tenantId, $sellerId);
        }

        $metrics = [
            'orders_count' => $result->orders_count,
            'orders_revenue' => (float) $result->orders_revenue,
            'orders_aov' => (float) $result->orders_aov,
            'products_viewed' => $result->products_viewed,
            'products_sold' => $result->products_sold,
            'unique_customers' => $result->unique_customers,
            'conversion_rate' => (float) $result->conversion_rate,
            'refunds_count' => $result->refunds_count,
            'refunds_amount' => (float) $result->refunds_amount,
            'seller_rating' => (float) $result->seller_rating,
        ];

        $comparison = [];
        if ($withComparison) {
            $previousPeriod = $period->getPreviousPeriod();
            $comparison = $this->getSellerAggregatedMetrics($previousPeriod, $tenantId, $sellerId, false)->toArray()['metrics'];
        }

        return AggregatedMetricsDto::create($period, $metrics, $tenantId, $sellerId, $comparison);
    }

    /**
     * Get top products by metric.
     */
    private function getTopProducts(string $metric, Period $period, int $tenantId, int $limit): TopItemsDto
    {
        $metricColumn = $this->mapMetricToColumn($metric);
        
        $results = $this->db->table('analytics_product_metrics')
            ->select('product_id', 'seller_id')
            ->selectRaw("SUM({$metricColumn}) as total")
            ->where('tenant_id', $tenantId)
            ->whereBetween('date', [$period->from()->toDateString(), $period->to()->toDateString()])
            ->groupBy('product_id', 'seller_id')
            ->orderBy('total', 'desc')
            ->limit($limit)
            ->get();

        $items = [];
        foreach ($results as $result) {
            $items[] = TopItemDto::create(
                $result->product_id,
                "Product #{$result->product_id}",
                $result->total,
            );
        }

        return TopItemsDto::create('product', $metric, $period, $items, $tenantId);
    }

    /**
     * Get top sellers by metric.
     */
    private function getTopSellers(string $metric, Period $period, int $tenantId, int $limit): TopItemsDto
    {
        $metricColumn = $this->mapMetricToColumn($metric);
        
        $results = $this->db->table('analytics_seller_metrics')
            ->select('seller_id')
            ->selectRaw("SUM({$metricColumn}) as total")
            ->where('tenant_id', $tenantId)
            ->whereBetween('date', [$period->from()->toDateString(), $period->to()->toDateString()])
            ->groupBy('seller_id')
            ->orderBy('total', 'desc')
            ->limit($limit)
            ->get();

        $items = [];
        foreach ($results as $result) {
            $items[] = TopItemDto::create(
                $result->seller_id,
                "Seller #{$result->seller_id}",
                $result->total,
            );
        }

        return TopItemsDto::create('seller', $metric, $period, $items, $tenantId);
    }

    /**
     * Get top categories by metric.
     */
    private function getTopCategories(string $metric, Period $period, int $tenantId, int $limit): TopItemsDto
    {
        $items = [];
        
        return TopItemsDto::create('category', $metric, $period, $items, $tenantId);
    }

    /**
     * Map metric type to database column.
     */
    private function mapMetricTypeToColumn(MetricType $metricType): string
    {
        return match ((string) $metricType) {
            'orders.count' => 'orders_count',
            'orders.revenue' => 'orders_revenue',
            'orders.aov' => 'orders_aov',
            'users.active' => 'users_active',
            'users.new' => 'users_new',
            'products.viewed' => 'products_viewed',
            'products.added_to_cart' => 'products_added_to_cart',
            'sellers.active' => 'sellers_active',
            'sessions' => 'sessions',
            'page_views' => 'page_views',
            'gmv' => 'gmv',
            default => throw new \InvalidArgumentException("Unknown metric type: {$metricType}"),
        };
    }

    /**
     * Map metric name to column.
     */
    private function mapMetricToColumn(string $metric): string
    {
        return match ($metric) {
            'revenue', 'orders_revenue' => 'orders_revenue',
            'orders', 'orders_count' => 'orders_count',
            'views', 'products_viewed' => 'products_viewed',
            default => str_replace('.', '_', $metric),
        };
    }

    /**
     * Get cache key for aggregated metrics.
     */
    private function getAggregatedCacheKey(
        string $type,
        Period $period,
        int $tenantId,
        ?int $sellerId = null,
    ): string {
        $key = "analytics:{$type}:{$tenantId}:{$period}";
        
        if ($sellerId !== null) {
            $key .= ":seller:{$sellerId}";
        }

        return $key;
    }

    /**
     * Get cache key for time series.
     */
    private function getTimeSeriesCacheKey(
        MetricType $metricType,
        Period $period,
        int $tenantId,
        ?int $sellerId = null,
        string $groupBy = 'day',
    ): string {
        return "analytics:timeseries:{$metricType}:{$tenantId}:{$period}:{$groupBy}" . ($sellerId ? ":seller:{$sellerId}" : '');
    }

    /**
     * Get cache key for top items.
     */
    private function getTopItemsCacheKey(
        string $itemType,
        string $metric,
        Period $period,
        int $tenantId,
        int $limit,
    ): string {
        return "analytics:top:{$itemType}:{$metric}:{$tenantId}:{$period}:{$limit}";
    }
}
