<?php

declare(strict_types=1);

namespace Modules\BigData\Application\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Facade;
use Modules\BigData\Domain\DTOs\BaseEventDTO;
use Modules\BigData\Domain\Enums\EventType;

/**
 * Big Data Facade
 *
 * Laravel Facade for easy access to Big Data functionality.
 * Usage: BigData::track($event)
 */
class BigDataFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return BigDataService::class;
    }

    /**
     * Track an event
     */
    public static function track(BaseEventDTO $event): bool
    {
        return static::getFacadeRoot()->track($event);
    }

    /**
     * Track event with parameters
     */
    public static function trackEvent(
        EventType $eventType,
        int $tenantId,
        ?int $userId = null,
        ?int $sellerId = null,
        ?int $productId = null,
        ?int $orderId = null,
        ?string $sessionId = null,
        ?string $vertical = null,
        array $properties = [],
        ?float $monetaryValue = null,
        array $context = [],
    ): bool {
        return static::getFacadeRoot()->trackEvent(
            $eventType,
            $tenantId,
            $userId,
            $sellerId,
            $productId,
            $orderId,
            $sessionId,
            $vertical,
            $properties,
            $monetaryValue,
            $context,
        );
    }

    /**
     * Get seller metrics
     */
    public static function sellerMetrics(
        int $tenantId,
        int $sellerId,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
    ): array {
        return static::getFacadeRoot()->getSellerMetrics($tenantId, $sellerId, $startDate, $endDate);
    }

    /**
     * Get seller summary
     */
    public static function sellerSummary(int $tenantId, int $sellerId, int $days = 30): array
    {
        return static::getFacadeRoot()->getSellerSummary($tenantId, $sellerId, $days);
    }

    /**
     * Get daily GMV
     */
    public static function dailyGMV(int $tenantId, CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        return static::getFacadeRoot()->getDailyGMV($tenantId, $startDate, $endDate);
    }

    /**
     * Get CLV prediction
     */
    public static function clv(int $tenantId, int $userId): ?array
    {
        return static::getFacadeRoot()->getCLVPrediction($tenantId, $userId);
    }

    /**
     * Get top sellers
     */
    public static function topSellers(int $tenantId, int $days = 30, int $limit = 10): array
    {
        return static::getFacadeRoot()->getTopSellersByGMV($tenantId, $days, $limit);
    }

    /**
     * Get A/B test results
     */
    public static function abTestResults(string $testId, int $tenantId): array
    {
        return static::getFacadeRoot()->getABTestResults($testId, $tenantId);
    }

    /**
     * Get event counts
     */
    public static function eventCounts(int $tenantId, CarbonImmutable $since): array
    {
        return static::getFacadeRoot()->getEventCounts($tenantId, $since);
    }

    /**
     * Get buyer-seller affinity
     */
    public static function buyerSellerAffinity(int $tenantId, int $buyerId, int $sellerId): ?array
    {
        return static::getFacadeRoot()->getBuyerSellerAffinity($tenantId, $buyerId, $sellerId);
    }

    /**
     * Execute custom query
     */
    public static function query(string $sql, array $params = []): array
    {
        return static::getFacadeRoot()->executeQuery($sql, $params);
    }

    /**
     * Health check
     */
    public static function health(): array
    {
        return static::getFacadeRoot()->healthCheck();
    }

    /**
     * Access monitoring facade
     *
     * Usage:
     *   BigData::monitor()->getPipelineHealth()
     *   BigData::monitor()->getDataFreshness('seller_metrics')
     *   BigData::monitor()->getCLVModelDrift()
     *   BigData::monitor()->getQueryPerformance('top_products')
     *   BigData::monitor()->alertIfLagOver('kafka.raw_events', 300)
     */
    public static function monitor(): BigDataMonitoringFacade
    {
        return app(BigDataMonitoringFacade::class);
    }

    /**
     * Access cost / FinOps facade
     *
     * Usage:
     *   BigData::cost()->getDailyBreakdown($date)
     *   BigData::cost()->getSellerAttribution($sellerId)
     *   BigData::cost()->predictMonthly()
     *   BigData::cost()->optimizeRecommendations()
     *   BigData::cost()->detectAnomalies()
     *   BigData::cost()->getUnitEconomics()
     */
    public static function cost(): BigDataCostFacade
    {
        return app(BigDataCostFacade::class);
    }
}
