<?php

declare(strict_types=1);

namespace Modules\Analytics\Application\Services;

use App\Traits\WithAuditLogging;
use App\Services\AuditService;
use Carbon\CarbonImmutable;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Log\LogManager;
use Modules\Warehouse\Domain\Enums\OrderTypeEnum;

final readonly class VerticalAnalyticsIntegrationService
{
    use WithAuditLogging;

    private const CACHE_TTL = 3600;

    public function __construct(
        private AuditService $auditService,
        private readonly ConnectionInterface $db,
        private readonly Repository $cache,
        private readonly LogManager $log,
    ) {}

    public function getDemandMetrics(
        string $vertical,
        ?string $tenantId = null,
        CarbonImmutable $fromDate = null,
        CarbonImmutable $toDate = null
    ): array {
        $fromDate ??= CarbonImmutable::now()->subDays(30);
        $toDate ??= CarbonImmutable::now();

        $cacheKey = "demand_metrics_{$vertical}_{$tenantId}_{$fromDate->format('Y-m-d')}_{$toDate->format('Y-m-d')}";

        return $this->cache->remember($cacheKey, self::CACHE_TTL, function () use ($vertical, $tenantId, $fromDate, $toDate) {
            $metrics = $this->fetchDemandData($vertical, $tenantId, $fromDate, $toDate);

            return [
                'vertical' => $vertical,
                'period' => [
                    'from' => $fromDate->format('Y-m-d'),
                    'to' => $toDate->format('Y-m-d'),
                ],
                'total_orders' => $metrics['total_orders'],
                'daily_average' => $metrics['daily_average'],
                'trend' => $this->calculateTrend($metrics['daily_data']),
                'peak_hours' => $metrics['peak_hours'],
                'peak_days' => $metrics['peak_days'],
                'by_category' => $metrics['by_category'],
                'growth_rate' => $this->calculateGrowthRate($metrics['daily_data']),
            ];
        });
    }

    public function getSalesMetrics(
        string $vertical,
        ?string $tenantId = null,
        CarbonImmutable $fromDate = null,
        CarbonImmutable $toDate = null
    ): array {
        $fromDate ??= CarbonImmutable::now()->subDays(30);
        $toDate ??= CarbonImmutable::now();

        $cacheKey = "sales_metrics_{$vertical}_{$tenantId}_{$fromDate->format('Y-m-d')}_{$toDate->format('Y-m-d')}";

        return $this->cache->remember($cacheKey, self::CACHE_TTL, function () use ($vertical, $tenantId, $fromDate, $toDate) {
            $metrics = $this->fetchSalesData($vertical, $tenantId, $fromDate, $toDate);

            return [
                'vertical' => $vertical,
                'period' => [
                    'from' => $fromDate->format('Y-m-d'),
                    'to' => $toDate->format('Y-m-d'),
                ],
                'total_revenue' => $metrics['total_revenue'],
                'average_order_value' => $metrics['average_order_value'],
                'total_orders' => $metrics['total_orders'],
                'conversion_rate' => $metrics['conversion_rate'],
                'by_payment_method' => $metrics['by_payment_method'],
                'by_order_type' => [
                    'b2b' => $metrics['b2b_revenue'],
                    'b2c' => $metrics['b2c_revenue'],
                ],
                'revenue_trend' => $this->calculateTrend($metrics['daily_revenue']),
                'top_products' => $metrics['top_products'],
            ];
        });
    }

    public function getQualityMetrics(
        string $vertical,
        ?string $tenantId = null,
        CarbonImmutable $fromDate = null,
        CarbonImmutable $toDate = null
    ): array {
        $fromDate ??= CarbonImmutable::now()->subDays(30);
        $toDate ??= CarbonImmutable::now();

        $cacheKey = "quality_metrics_{$vertical}_{$tenantId}_{$fromDate->format('Y-m-d')}_{$toDate->format('Y-m-d')}";

        return $this->cache->remember($cacheKey, self::CACHE_TTL, function () use ($vertical, $tenantId, $fromDate, $toDate) {
            $metrics = $this->fetchQualityData($vertical, $tenantId, $fromDate, $toDate);

            return [
                'vertical' => $vertical,
                'period' => [
                    'from' => $fromDate->format('Y-m-d'),
                    'to' => $toDate->format('Y-m-d'),
                ],
                'average_rating' => $metrics['average_rating'],
                'total_reviews' => $metrics['total_reviews'],
                'rating_distribution' => $metrics['rating_distribution'],
                'complaint_rate' => $metrics['complaint_rate'],
                'return_rate' => $metrics['return_rate'],
                'on_time_delivery_rate' => $metrics['on_time_delivery_rate'],
                'quality_score' => $this->calculateQualityScore($metrics),
            ];
        });
    }

    public function getPublicMetrics(
        string $vertical,
        ?string $tenantId = null,
        CarbonImmutable $fromDate = null,
        CarbonImmutable $toDate = null
    ): array {
        $fromDate ??= CarbonImmutable::now()->subDays(30);
        $toDate ??= CarbonImmutable::now();

        $cacheKey = "public_metrics_{$vertical}_{$tenantId}_{$fromDate->format('Y-m-d')}_{$toDate->format('Y-m-d')}";

        return $this->cache->remember($cacheKey, self::CACHE_TTL, function () use ($vertical, $tenantId, $fromDate, $toDate) {
            $metrics = $this->fetchPublicData($vertical, $tenantId, $fromDate, $toDate);

            return [
                'vertical' => $vertical,
                'period' => [
                    'from' => $fromDate->format('Y-m-d'),
                    'to' => $toDate->format('Y-m-d'),
                ],
                'social_media_mentions' => $metrics['social_mentions'],
                'sentiment_score' => $metrics['sentiment_score'],
                'engagement_rate' => $metrics['engagement_rate'],
                'follower_growth' => $metrics['follower_growth'],
                'share_of_voice' => $metrics['share_of_voice'],
                'viral_content' => $metrics['viral_content'],
                'influencer_mentions' => $metrics['influencer_mentions'],
            ];
        });
    }

    public function getVerticalDashboard(
        string $vertical,
        ?string $tenantId = null,
        CarbonImmutable $fromDate = null,
        CarbonImmutable $toDate = null
    ): array {
        return [
            'demand' => $this->getDemandMetrics($vertical, $tenantId, $fromDate, $toDate),
            'sales' => $this->getSalesMetrics($vertical, $tenantId, $fromDate, $toDate),
            'quality' => $this->getQualityMetrics($vertical, $tenantId, $fromDate, $toDate),
            'public' => $this->getPublicMetrics($vertical, $tenantId, $fromDate, $toDate),
        ];
    }

    public function getCrossVerticalComparison(
        array $verticals,
        ?string $tenantId = null,
        CarbonImmutable $fromDate = null,
        CarbonImmutable $toDate = null
    ): array {
        $comparison = [];

        foreach ($verticals as $vertical) {
            $comparison[$vertical] = [
                'demand' => $this->getDemandMetrics($vertical, $tenantId, $fromDate, $toDate),
                'sales' => $this->getSalesMetrics($vertical, $tenantId, $fromDate, $toDate),
                'quality' => $this->getQualityMetrics($vertical, $tenantId, $fromDate, $toDate),
            ];
        }

        return [
            'period' => [
                'from' => $fromDate?->format('Y-m-d') ?? CarbonImmutable::now()->subDays(30)->format('Y-m-d'),
                'to' => $toDate?->format('Y-m-d') ?? CarbonImmutable::now()->format('Y-m-d'),
            ],
            'verticals' => $comparison,
            'ranking' => $this->calculateVerticalRanking($comparison),
        ];
    }

    public function recordMetric(
        string $vertical,
        string $metricType,
        array $data,
        ?string $tenantId = null
    ): void {
        $metricData = [
            'vertical' => $vertical,
            'metric_type' => $metricType,
            'tenant_id' => $tenantId,
            'data' => $data,
            'recorded_at' => now(),
        ];

        $this->db->table('analytics_metrics')->insert($metricData);

        $this->invalidateMetricCache($vertical, $metricType, $tenantId);

        $this->logAction(
            action: 'analytics.metric_recorded',
            entityType: 'AnalyticsMetric',
            entityId: null,
            context: [
                'vertical' => $vertical,
                'metric_type' => $metricType,
            ],
            userId: null,
            tenantId: $tenantId
        );
    }

    private function fetchDemandData(string $vertical, ?string $tenantId, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $query = $this->db->table('orders')
            ->whereBetween('created_at', [$from, $to])
            ->where('vertical', $vertical);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        $orders = $query->get();

        $dailyData = $orders->groupBy(fn($o) => CarbonImmutable::parse($o->created_at)->format('Y-m-d'))
            ->map(fn($dayOrders) => $dayOrders->count())
            ->toArray();

        return [
            'total_orders' => $orders->count(),
            'daily_average' => $orders->count() / max(1, $from->diffInDays($to)),
            'daily_data' => $dailyData,
            'peak_hours' => $this->calculatePeakHours($orders),
            'peak_days' => $this->calculatePeakDays($dailyData),
            'by_category' => $this->groupByCategory($orders),
        ];
    }

    private function fetchSalesData(string $vertical, ?string $tenantId, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $query = $this->db->table('orders')
            ->whereBetween('created_at', [$from, $to])
            ->where('vertical', $vertical)
            ->where('status', 'completed');

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        $orders = $query->get();

        $dailyRevenue = $orders->groupBy(fn($o) => CarbonImmutable::parse($o->created_at)->format('Y-m-d'))
            ->map(fn($dayOrders) => $dayOrders->sum('total'))
            ->toArray();

        return [
            'total_revenue' => $orders->sum('total'),
            'average_order_value' => $orders->avg('total'),
            'total_orders' => $orders->count(),
            'conversion_rate' => $this->calculateConversionRate($orders),
            'by_payment_method' => $orders->groupBy('payment_method')->map(fn($g) => $g->count()),
            'b2b_revenue' => $orders->where('order_type', 'b2b')->sum('total'),
            'b2c_revenue' => $orders->where('order_type', 'b2c')->sum('total'),
            'daily_revenue' => $dailyRevenue,
            'top_products' => $this->getTopProducts($orders),
        ];
    }

    private function fetchQualityData(string $vertical, ?string $tenantId, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $query = $this->db->table('reviews')
            ->whereBetween('created_at', [$from, $to])
            ->where('vertical', $vertical);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        $reviews = $query->get();

        return [
            'average_rating' => $reviews->avg('rating'),
            'total_reviews' => $reviews->count(),
            'rating_distribution' => $reviews->groupBy('rating')->map(fn($g) => $g->count()),
            'complaint_rate' => $this->calculateComplaintRate($vertical, $tenantId, $from, $to),
            'return_rate' => $this->calculateReturnRate($vertical, $tenantId, $from, $to),
            'on_time_delivery_rate' => $this->calculateOnTimeRate($vertical, $tenantId, $from, $to),
        ];
    }

    private function fetchPublicData(string $vertical, ?string $tenantId, CarbonImmutable $from, CarbonImmutable $to): array
    {
        return [
            'social_mentions' => $this->getSocialMentions($vertical, $tenantId, $from, $to),
            'sentiment_score' => $this->getSentimentScore($vertical, $tenantId, $from, $to),
            'engagement_rate' => $this->getEngagementRate($vertical, $tenantId, $from, $to),
            'follower_growth' => $this->getFollowerGrowth($vertical, $tenantId, $from, $to),
            'share_of_voice' => $this->getShareOfVoice($vertical, $tenantId, $from, $to),
            'viral_content' => $this->getViralContent($vertical, $tenantId, $from, $to),
            'influencer_mentions' => $this->getInfluencerMentions($vertical, $tenantId, $from, $to),
        ];
    }

    private function calculateTrend(array $data): string
    {
        if (count($data) < 2) {
            return 'stable';
        }

        $firstHalf = array_slice(array_values($data), 0, count($data) / 2);
        $secondHalf = array_slice(array_values($data), count($data) / 2);

        $firstAvg = array_sum($firstHalf) / count($firstHalf);
        $secondAvg = array_sum($secondHalf) / count($secondHalf);

        if ($secondAvg > $firstAvg * 1.1) {
            return 'increasing';
        } elseif ($secondAvg < $firstAvg * 0.9) {
            return 'decreasing';
        }

        return 'stable';
    }

    private function calculateGrowthRate(array $dailyData): float
    {
        if (count($dailyData) < 2) {
            return 0.0;
        }

        $values = array_values($dailyData);
        $first = $values[0];
        $last = end($values);

        if ($first === 0) {
            return $last > 0 ? 100.0 : 0.0;
        }

        return (($last - $first) / $first) * 100;
    }

    private function calculateQualityScore(array $metrics): float
    {
        $ratingScore = ($metrics['average_rating'] / 5) * 40;
        $complaintScore = (1 - min(1, $metrics['complaint_rate'] / 10)) * 30;
        $returnScore = (1 - min(1, $metrics['return_rate'] / 20)) * 15;
        $deliveryScore = $metrics['on_time_delivery_rate'] * 0.15;

        return $ratingScore + $complaintScore + $returnScore + $deliveryScore;
    }

    private function calculateVerticalRanking(array $comparison): array
    {
        $rankings = [
            'revenue' => [],
            'orders' => [],
            'quality' => [],
        ];

        foreach ($comparison as $vertical => $data) {
            $rankings['revenue'][$vertical] = $data['sales']['total_revenue'];
            $rankings['orders'][$vertical] = $data['demand']['total_orders'];
            $rankings['quality'][$vertical] = $data['quality']['quality_score'];
        }

        foreach ($rankings as $key => &$ranking) {
            arsort($ranking);
            $ranking = array_keys($ranking);
        }

        return $rankings;
    }

    private function calculatePeakHours($orders): array
    {
        $hourly = $orders->groupBy(fn($o) => CarbonImmutable::parse($o->created_at)->hour)
            ->map(fn($h) => $h->count())
            ->sortDesc()
            ->take(3)
            ->keys()
            ->toArray();

        return $hourly;
    }

    private function calculatePeakDays(array $dailyData): array
    {
        arsort($dailyData);
        return array_slice(array_keys($dailyData), 0, 3);
    }

    private function groupByCategory($orders): array
    {
        return $orders->groupBy('category')->map(fn($g) => $g->count())->toArray();
    }

    private function calculateConversionRate($orders): float
    {
        $total = $orders->count();
        $completed = $orders->where('status', 'completed')->count();

        return $total > 0 ? ($completed / $total) * 100 : 0;
    }

    private function getTopProducts($orders): array
    {
        return $orders->pluck('items')
            ->flatten()
            ->groupBy('product_id')
            ->map(fn($g) => $g->sum('quantity'))
            ->sortDesc()
            ->take(10)
            ->toArray();
    }

    private function calculateComplaintRate(string $vertical, ?string $tenantId, CarbonImmutable $from, CarbonImmutable $to): float
    {
        $orders = $this->db->table('orders')
            ->whereBetween('created_at', [$from, $to])
            ->where('vertical', $vertical);

        if ($tenantId) {
            $orders->where('tenant_id', $tenantId);
        }

        $total = $orders->count();
        $complaints = $this->db->table('complaints')
            ->whereBetween('created_at', [$from, $to])
            ->where('vertical', $vertical);

        if ($tenantId) {
            $complaints->where('tenant_id', $tenantId);
        }

        return $total > 0 ? ($complaints->count() / $total) * 100 : 0;
    }

    private function calculateReturnRate(string $vertical, ?string $tenantId, CarbonImmutable $from, CarbonImmutable $to): float
    {
        $orders = $this->db->table('orders')
            ->whereBetween('created_at', [$from, $to])
            ->where('vertical', $vertical);

        if ($tenantId) {
            $orders->where('tenant_id', $tenantId);
        }

        $total = $orders->count();
        $returns = $this->db->table('returns')
            ->whereBetween('created_at', [$from, $to])
            ->where('vertical', $vertical);

        if ($tenantId) {
            $returns->where('tenant_id', $tenantId);
        }

        return $total > 0 ? ($returns->count() / $total) * 100 : 0;
    }

    private function calculateOnTimeRate(string $vertical, ?string $tenantId, CarbonImmutable $from, CarbonImmutable $to): float
    {
        $orders = $this->db->table('orders')
            ->whereBetween('created_at', [$from, $to])
            ->where('vertical', $vertical)
            ->whereNotNull('delivered_at');

        if ($tenantId) {
            $orders->where('tenant_id', $tenantId);
        }

        $total = $orders->count();
        $onTime = $orders->whereColumn('delivered_at', '<=', 'estimated_delivery_at')->count();

        return $total > 0 ? ($onTime / $total) * 100 : 0;
    }

    private function getSocialMentions(string $vertical, ?string $tenantId, CarbonImmutable $from, CarbonImmutable $to): int
    {
        return $this->db->table('social_mentions')
            ->whereBetween('created_at', [$from, $to])
            ->where('vertical', $vertical)
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->count();
    }

    private function getSentimentScore(string $vertical, ?string $tenantId, CarbonImmutable $from, CarbonImmutable $to): float
    {
        return $this->db->table('social_mentions')
            ->whereBetween('created_at', [$from, $to])
            ->where('vertical', $vertical)
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->avg('sentiment_score') ?? 0;
    }

    private function getEngagementRate(string $vertical, ?string $tenantId, CarbonImmutable $from, CarbonImmutable $to): float
    {
        return $this->db->table('social_analytics')
            ->whereBetween('date', [$from, $to])
            ->where('vertical', $vertical)
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->avg('engagement_rate') ?? 0;
    }

    private function getFollowerGrowth(string $vertical, ?string $tenantId, CarbonImmutable $from, CarbonImmutable $to): float
    {
        $start = $this->db->table('social_analytics')
            ->where('date', $from)
            ->where('vertical', $vertical)
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->value('followers') ?? 0;

        $end = $this->db->table('social_analytics')
            ->where('date', $to)
            ->where('vertical', $vertical)
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->value('followers') ?? 0;

        return $start > 0 ? (($end - $start) / $start) * 100 : 0;
    }

    private function getShareOfVoice(string $vertical, ?string $tenantId, CarbonImmutable $from, CarbonImmutable $to): float
    {
        return $this->db->table('market_analytics')
            ->whereBetween('date', [$from, $to])
            ->where('vertical', $vertical)
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->avg('share_of_voice') ?? 0;
    }

    private function getViralContent(string $vertical, ?string $tenantId, CarbonImmutable $from, CarbonImmutable $to): array
    {
        return $this->db->table('social_mentions')
            ->whereBetween('created_at', [$from, $to])
            ->where('vertical', $vertical)
            ->where('is_viral', true)
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->limit(10)
            ->get()
            ->toArray();
    }

    private function getInfluencerMentions(string $vertical, ?string $tenantId, CarbonImmutable $from, CarbonImmutable $to): int
    {
        return $this->db->table('social_mentions')
            ->whereBetween('created_at', [$from, $to])
            ->where('vertical', $vertical)
            ->where('is_influencer', true)
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->count();
    }

    private function invalidateMetricCache(string $vertical, string $metricType, ?string $tenantId): void
    {
        $patterns = [
            "demand_metrics_{$vertical}_{$tenantId}",
            "sales_metrics_{$vertical}_{$tenantId}",
            "quality_metrics_{$vertical}_{$tenantId}",
            "public_metrics_{$vertical}_{$tenantId}",
        ];

        foreach ($patterns as $pattern) {
            if ($pattern) {
                $this->cache->forget($pattern);
            }
        }
    }
}
