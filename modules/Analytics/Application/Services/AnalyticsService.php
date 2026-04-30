<?php

declare(strict_types=1);

namespace Modules\Analytics\Application\Services;

use Carbon\CarbonImmutable;
use Modules\Analytics\Application\DTOs\AggregatedMetricsDto;
use Modules\Analytics\Application\DTOs\FunnelDto;
use Modules\Analytics\Application\DTOs\RetentionCohortDto;
use Modules\Analytics\Application\DTOs\TimeSeriesDto;
use Modules\Analytics\Application\DTOs\TopItemsDto;
use Modules\Analytics\Domain\ValueObjects\MetricType;
use Modules\Analytics\Domain\ValueObjects\Period;

/**
 * Analytics Service (Facade)
 *
 * Facade service providing backward compatibility while delegating to specialized services.
 * Refactored to follow SRP by splitting responsibilities into:
 * - EventTrackingService: event ingestion and metric increments
 * - MetricsQueryService: aggregated metrics, time-series, top items
 * - FunnelAnalysisService: funnel analysis
 * - RetentionAnalysisService: retention cohort analysis
 * - UserAnalyticsService: user-specific analytics and GDPR
 * 
 * Production-ready with:
 * - Multi-tenant support
 * - Caching with proper invalidation
 * - Async job dispatching for heavy operations
 * - GDPR compliance (anonymization support)
 * - Integration with Audit service
 */
final readonly class AnalyticsService
{
    public function __construct(
        private readonly EventTrackingService $eventTracking,
        private readonly MetricsQueryService $metricsQuery,
        private readonly FunnelAnalysisService $funnelAnalysis,
        private readonly RetentionAnalysisService $retentionAnalysis,
        private readonly UserAnalyticsService $userAnalytics,
    ) {}

    /**
     * Track an analytics event.
     * 
     * Delegates to EventTrackingService.
     */
    public function trackEvent(
        string $eventType,
        int $tenantId,
        ?int $userId = null,
        ?string $entityType = null,
        ?int $entityId = null,
        array $metadata = [],
        ?float $monetaryValue = null,
        array $dimensions = [],
    ): void {
        $this->eventTracking->trackEvent(
            $eventType,
            $tenantId,
            $userId,
            $entityType,
            $entityId,
            $metadata,
            $monetaryValue,
            $dimensions,
        );
    }

    /**
     * Increment a metric counter.
     * 
     * Delegates to EventTrackingService.
     */
    public function increment(
        string $metricType,
        int $tenantId,
        float|int $value = 1,
        ?CarbonImmutable $date = null,
        ?int $sellerId = null,
        ?int $productId = null,
        ?int $userId = null,
    ): void {
        $this->eventTracking->increment(
            $metricType,
            $tenantId,
            $value,
            $date,
            $sellerId,
            $productId,
            $userId,
        );
    }

    /**
     * Get aggregated metrics for a period.
     * 
     * Delegates to MetricsQueryService.
     */
    public function getAggregatedMetrics(
        Period $period,
        int $tenantId,
        ?int $sellerId = null,
        bool $withComparison = true,
    ): AggregatedMetricsDto {
        return $this->metricsQuery->getAggregatedMetrics($period, $tenantId, $sellerId, $withComparison);
    }

    /**
     * Get time-series data for a metric.
     * 
     * Delegates to MetricsQueryService.
     */
    public function getTimeSeries(
        MetricType $metricType,
        Period $period,
        int $tenantId,
        ?int $sellerId = null,
        string $groupBy = 'day',
    ): TimeSeriesDto {
        return $this->metricsQuery->getTimeSeries($metricType, $period, $tenantId, $sellerId, $groupBy);
    }

    /**
     * Get top items (products, sellers, categories).
     * 
     * Delegates to MetricsQueryService.
     */
    public function getTopItems(
        string $itemType,
        string $metric,
        Period $period,
        int $tenantId,
        int $limit = 10,
    ): TopItemsDto {
        return $this->metricsQuery->getTopItems($itemType, $metric, $period, $tenantId, $limit);
    }

    /**
     * Get funnel analysis.
     * 
     * Delegates to FunnelAnalysisService.
     */
    public function getFunnel(
        string $funnelName,
        Period $period,
        int $tenantId,
        ?string $category = null,
        ?int $sellerId = null,
    ): FunnelDto {
        return $this->funnelAnalysis->getFunnel($funnelName, $period, $tenantId, $category, $sellerId);
    }

    /**
     * Get retention cohort analysis.
     * 
     * Delegates to RetentionAnalysisService.
     */
    public function getRetentionCohorts(
        string $cohortType,
        CarbonImmutable $analysisDate,
        int $tenantId,
    ): RetentionCohortDto {
        return $this->retentionAnalysis->getRetentionCohorts($cohortType, $analysisDate, $tenantId);
    }

    /**
     * GDPR: Delete all analytics data for a user.
     * 
     * Delegates to UserAnalyticsService.
     */
    public function deleteUserAnalytics(int $userId, int $tenantId): void
    {
        $this->userAnalytics->deleteUserAnalytics($userId, $tenantId);
    }
}
