<?php

declare(strict_types=1);

namespace Modules\BigData\Domain\Interfaces;

use Carbon\CarbonImmutable;
use Modules\BigData\Domain\DTOs\DailyCostDTO;
use Modules\BigData\Domain\DTOs\SellerCostDTO;
use Modules\BigData\Domain\DTOs\CostPredictionDTO;
use Modules\BigData\Domain\Entities\CostAttribution;
use Modules\BigData\Domain\Entities\CostBreakdown;
use Modules\BigData\Domain\Entities\OptimizationRecommendation;

/**
 * Cost Repository Interface
 *
 * Domain interface for cost data access.
 * Implementation lives in Infrastructure layer (ClickHouseCostRepository).
 */
interface CostRepositoryInterface
{
    /**
     * Get daily cost breakdown
     */
    public function getDailyBreakdown(CarbonImmutable $date): CostBreakdown;

    /**
     * Get daily cost time series
     * @return array<DailyCostDTO>
     */
    public function getDailyCostTimeSeries(CarbonImmutable $startDate, CarbonImmutable $endDate): array;

    /**
     * Get cost attribution for a specific seller
     */
    public function getSellerAttribution(int $tenantId, int $sellerId, CarbonImmutable $startDate, CarbonImmutable $endDate): SellerCostDTO;

    /**
     * Get cost attribution by bounded context
     * @return array<CostAttribution>
     */
    public function getAttributionByContext(CarbonImmutable $startDate, CarbonImmutable $endDate): array;

    /**
     * Get top N most expensive sellers
     * @return array<SellerCostDTO>
     */
    public function getTopExpensiveSellers(int $tenantId, int $limit = 20): array;

    /**
     * Predict monthly cost based on current trend
     */
    public function predictMonthlyCost(): CostPredictionDTO;

    /**
     * Get ClickHouse internal cost metrics
     */
    public function getClickHouseCostMetrics(CarbonImmutable $date): array;

    /**
     * Get Kafka cost metrics
     */
    public function getKafkaCostMetrics(CarbonImmutable $date): array;

    /**
     * Get Spark/ML cost metrics
     */
    public function getSparkMLCostMetrics(CarbonImmutable $startDate, CarbonImmutable $endDate): array;

    /**
     * Get top expensive queries for a date
     * @return array<array{query_hash: string, cost_usd: float, rows: int, seller_id: int}>
     */
    public function getTopExpensiveQueries(CarbonImmutable $date, int $limit = 10): array;

    /**
     * Get compression ratio for ClickHouse tables
     * @return array<string, float> table => ratio
     */
    public function getCompressionRatios(): array;

    /**
     * Get storage growth rate (% per week)
     */
    public function getStorageGrowthRate(): float;

    /**
     * Get materialized view ROI
     * @return array<array{mv_name: string, storage_bytes: int, savings_usd: float, roi: float}>
     */
    public function getMaterializedViewROI(): array;

    /**
     * Insert billing raw records
     */
    public function insertBillingRaw(array $records): int;

    /**
     * Insert cost attribution records
     */
    public function insertCostAttribution(array $records): int;

    /**
     * Insert cost anomaly
     */
    public function insertCostAnomaly(array $anomaly): void;

    /**
     * Insert optimization recommendation
     */
    public function insertOptimizationRecommendation(array $recommendation): void;

    /**
     * Get cost anomalies
     * @return array<array<string, mixed>>
     */
    public function getOpenAnomalies(): array;

    /**
     * Get optimization recommendations
     * @return array<OptimizationRecommendation>
     */
    public function getPendingRecommendations(): array;

    /**
     * Apply optimization recommendation
     */
    public function markRecommendationApplied(string $recommendationId, float $actualSavingsUsd): void;

    /**
     * Get unit economics summary
     */
    public function getUnitEconomics(CarbonImmutable $startDate, CarbonImmutable $endDate): array;
}
