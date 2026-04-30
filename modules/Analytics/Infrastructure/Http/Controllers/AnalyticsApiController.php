<?php

declare(strict_types=1);

namespace Modules\Analytics\Infrastructure\Http\Controllers;

use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Analytics\Application\DTOs\AggregatedMetricsDto;
use Modules\Analytics\Application\DTOs\FunnelDto;
use Modules\Analytics\Application\DTOs\RetentionCohortDto;
use Modules\Analytics\Application\DTOs\TimeSeriesDto;
use Modules\Analytics\Application\DTOs\TopItemsDto;
use Modules\Analytics\Application\Facades\Analytics;
use Modules\Analytics\Domain\ValueObjects\MetricType;
use Modules\Analytics\Domain\ValueObjects\Period;

/**
 * Analytics API Controller
 *
 * Provides REST API endpoints for external BI systems (Metabase, Power BI, Looker Studio).
 * Supports multi-tenant access with proper authentication and authorization.
 * 
 * TODO: Add authentication middleware (API keys, OAuth, etc.)
 * TODO: Add rate limiting
 * TODO: Add request validation
 */
final class AnalyticsApiController extends Controller
{
    /**
     * Get aggregated metrics for a period.
     * 
     * Query parameters:
     * - period: today, last_7_days, last_30_days, last_90_days, custom
     * - from: start date (required for custom period)
     * - to: end date (required for custom period)
     * - seller_id: optional, for seller-specific metrics
     * - with_comparison: boolean, include previous period comparison
     */
    public function getMetrics(Request $request): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $periodType = $request->get('period', 'last_30_days');
        $sellerId = $request->get('seller_id') ? (int) $request->get('seller_id') : null;
        $withComparison = $request->boolean('with_comparison', true);

        $period = match ($periodType) {
            'today' => Period::today(),
            'yesterday' => Period::yesterday(),
            'last_7_days' => Period::last7Days(),
            'last_30_days' => Period::last30Days(),
            'last_90_days' => Period::last90Days(),
            'this_week' => Period::thisWeek(),
            'last_week' => Period::lastWeek(),
            'this_month' => Period::thisMonth(),
            'last_month' => Period::lastMonth(),
            'custom' => Period::custom(
                CarbonImmutable::parse($request->get('from')),
                CarbonImmutable::parse($request->get('to')),
            ),
            default => Period::last30Days(),
        };

        $metrics = Analytics::getAggregatedMetrics($period, $tenantId, $sellerId, $withComparison);

        return response()->json($metrics->toArray());
    }

    /**
     * Get time-series data for a specific metric.
     * 
     * Query parameters:
     * - metric_type: orders.count, orders.revenue, etc.
     * - period: today, last_7_days, last_30_days, etc.
     * - group_by: hour, day, week, month
     * - seller_id: optional
     */
    public function getTimeSeries(Request $request): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $metricType = MetricType::fromString($request->get('metric_type', 'orders.count'));
        $periodType = $request->get('period', 'last_30_days');
        $groupBy = $request->get('group_by', 'day');
        $sellerId = $request->get('seller_id') ? (int) $request->get('seller_id') : null;

        $period = match ($periodType) {
            'today' => Period::today(),
            'last_7_days' => Period::last7Days(),
            'last_30_days' => Period::last30Days(),
            'last_90_days' => Period::last90Days(),
            default => Period::last30Days(),
        };

        $timeSeries = Analytics::getTimeSeries($metricType, $period, $tenantId, $sellerId, $groupBy);

        return response()->json($timeSeries->toArray());
    }

    /**
     * Get top items (products, sellers, categories).
     * 
     * Query parameters:
     * - item_type: product, seller, category
     * - metric: revenue, orders, views, etc.
     * - period: last_7_days, last_30_days, etc.
     * - limit: number of items to return (default: 10)
     */
    public function getTopItems(Request $request): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $itemType = $request->get('item_type', 'product');
        $metric = $request->get('metric', 'revenue');
        $periodType = $request->get('period', 'last_30_days');
        $limit = (int) $request->get('limit', 10);

        $period = match ($periodType) {
            'last_7_days' => Period::last7Days(),
            'last_30_days' => Period::last30Days(),
            'last_90_days' => Period::last90Days(),
            default => Period::last30Days(),
        };

        $topItems = Analytics::getTopItems($itemType, $metric, $period, $tenantId, $limit);

        return response()->json($topItems->toArray());
    }

    /**
     * Get funnel analysis.
     * 
     * Query parameters:
     * - funnel_name: checkout, product_conversion, etc.
     * - period: last_7_days, last_30_days, etc.
     * - category: optional, filter by category
     * - seller_id: optional, filter by seller
     */
    public function getFunnel(Request $request): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $funnelName = $request->get('funnel_name', 'checkout');
        $periodType = $request->get('period', 'last_30_days');
        $category = $request->get('category');
        $sellerId = $request->get('seller_id') ? (int) $request->get('seller_id') : null;

        $period = match ($periodType) {
            'last_7_days' => Period::last7Days(),
            'last_30_days' => Period::last30Days(),
            'last_90_days' => Period::last90Days(),
            default => Period::last30Days(),
        };

        $funnel = Analytics::getFunnel($funnelName, $period, $tenantId, $category, $sellerId);

        return response()->json($funnel->toArray());
    }

    /**
     * Get retention cohort analysis.
     * 
     * Query parameters:
     * - cohort_type: user, seller
     * - analysis_date: date to analyze (default: today)
     */
    public function getRetentionCohorts(Request $request): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $cohortType = $request->get('cohort_type', 'user');
        $analysisDate = $request->get('analysis_date')
            ? CarbonImmutable::parse($request->get('analysis_date'))
            : CarbonImmutable::now();

        $cohorts = Analytics::getRetentionCohorts($cohortType, $analysisDate, $tenantId);

        return response()->json($cohorts->toArray());
    }

    /**
     * Export metrics data for BI tools.
     * 
     * Query parameters:
     * - format: csv, json, excel
     * - period: last_30_days, etc.
     * - metrics: comma-separated list of metrics to export
     * 
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\JsonResponse
     */
    public function export(Request $request)
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $format = $request->get('format', 'json');
        $periodType = $request->get('period', 'last_30_days');
        $metrics = explode(',', $request->get('metrics', 'orders_count,orders_revenue'));

        $period = match ($periodType) {
            'last_7_days' => Period::last7Days(),
            'last_30_days' => Period::last30Days(),
            'last_90_days' => Period::last90Days(),
            default => Period::last30Days(),
        };

        $data = Analytics::getAggregatedMetrics($period, $tenantId)->toArray();

        // TODO: Implement CSV and Excel export
        return match ($format) {
            'csv' => $this->exportCsv($data, $metrics),
            'excel' => $this->exportExcel($data, $metrics),
            default => response()->json($data),
        };
    }

    /**
     * Get real-time metrics (from Redis/ClickHouse).
     * 
     * Returns metrics for the last 24 hours or shorter periods.
     */
    public function getRealtimeMetrics(Request $request): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $hours = (int) $request->get('hours', 24);

        // TODO: Implement real-time metrics from Redis/ClickHouse
        // For now, fall back to daily metrics
        $days = $hours <= 1 ? 1 : (int) ceil($hours / 24);
        $period = match (true) {
            $days <= 1 => Period::today(),
            $days <= 7 => Period::last7Days(),
            $days <= 30 => Period::last30Days(),
            default => Period::last90Days(),
        };
        $metrics = Analytics::getAggregatedMetrics($period, $tenantId);

        return response()->json($metrics->toArray());
    }

    /**
     * Get seller-specific analytics.
     */
    public function getSellerAnalytics(Request $request, int $sellerId): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $periodType = $request->get('period', 'last_30_days');

        $period = match ($periodType) {
            'last_7_days' => Period::last7Days(),
            'last_30_days' => Period::last30Days(),
            'last_90_days' => Period::last90Days(),
            default => Period::last30Days(),
        };

        $metrics = Analytics::getAggregatedMetrics($period, $tenantId, $sellerId);

        return response()->json($metrics->toArray());
    }

    /**
     * Get seller analytics dashboard for mobile app.
     * 
     * Query parameters:
     * - period: today, last_7_days, last_30_days, last_90_days
     * 
     * Returns complete dashboard data: KPI cards, trends, top products, insights.
     */
    public function getSellerDashboard(Request $request): JsonResponse
    {
        $sellerId = auth()->id();
        $tenantId = tenant()?->id ?? (int) $request->header('X-Tenant-ID');
        $periodType = $request->get('period', 'last_30_days');

        if (!$sellerId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $period = match ($periodType) {
            'today' => Period::today(),
            'last_7_days' => Period::last7Days(),
            'last_30_days' => Period::last30Days(),
            'last_90_days' => Period::last90Days(),
            default => Period::last30Days(),
        };

        $dashboardData = app(SellerAnalyticsService::class)
            ->getDashboardData($sellerId, $period, $tenantId)
            ->toArray();

        return response()->json($dashboardData);
    }

    /**
     * Get seller product analytics table data.
     * 
     * Query parameters:
     * - period: last_7_days, last_30_days, last_90_days
     * - page: page number (default: 1)
     * - per_page: items per page (default: 50)
     */
    public function getSellerProductAnalytics(Request $request): JsonResponse
    {
        $sellerId = auth()->id();
        $tenantId = tenant()?->id ?? (int) $request->header('X-Tenant-ID');
        $periodType = $request->get('period', 'last_30_days');
        $page = (int) $request->get('page', 1);
        $perPage = (int) $request->get('per_page', 50);

        if (!$sellerId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $period = match ($periodType) {
            'last_7_days' => Period::last7Days(),
            'last_30_days' => Period::last30Days(),
            'last_90_days' => Period::last90Days(),
            default => Period::last30Days(),
        };

        $productAnalytics = app(SellerAnalyticsService::class)
            ->getProductAnalytics($sellerId, $period, $tenantId, $page, $perPage);

        return response()->json($productAnalytics);
    }

    /**
     * Get seller AI-powered insights.
     * 
     * Query parameters:
     * - period: last_7_days, last_30_days, last_90_days
     */
    public function getSellerInsights(Request $request): JsonResponse
    {
        $sellerId = auth()->id();
        $tenantId = tenant()?->id ?? (int) $request->header('X-Tenant-ID');
        $periodType = $request->get('period', 'last_30_days');

        if (!$sellerId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $period = match ($periodType) {
            'last_7_days' => Period::last7Days(),
            'last_30_days' => Period::last30Days(),
            'last_90_days' => Period::last90Days(),
            default => Period::last30Days(),
        };

        $insights = app(SellerAnalyticsService::class)
            ->generateInsights($sellerId, $period, $tenantId);

        return response()->json(array_map(fn ($insight) => $insight->toArray(), $insights));
    }

    /**
     * Track an analytics event (for external systems).
     */
    public function trackEvent(Request $request): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $eventType = $request->get('event_type');
        $userId = $request->get('user_id') ? (int) $request->get('user_id') : null;
        $entityType = $request->get('entity_type');
        $entityId = $request->get('entity_id') ? (int) $request->get('entity_id') : null;
        $metadata = $request->get('metadata', []);
        $monetaryValue = $request->get('monetary_value') ? (float) $request->get('monetary_value') : null;
        $dimensions = $request->get('dimensions', []);

        Analytics::trackEvent(
            eventType: $eventType,
            tenantId: $tenantId,
            userId: $userId,
            entityType: $entityType,
            entityId: $entityId,
            metadata: $metadata,
            monetaryValue: $monetaryValue,
            dimensions: $dimensions,
        );

        return response()->json(['status' => 'success']);
    }

    /**
     * Export to CSV format.
     */
    private function exportCsv(array $data, array $metrics)
    {
        // TODO: Implement CSV export using Laravel Excel or similar
        return response()->json(['error' => 'CSV export not yet implemented']);
    }

    /**
     * Export to Excel format.
     */
    private function exportExcel(array $data, array $metrics)
    {
        // TODO: Implement Excel export using Laravel Excel
        return response()->json(['error' => 'Excel export not yet implemented']);
    }
}
