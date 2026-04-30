<?php

declare(strict_types=1);

namespace Modules\BigData\Application\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Modules\BigData\Domain\DTOs\DailyCostDTO;
use Modules\BigData\Domain\DTOs\SellerCostDTO;
use Modules\BigData\Domain\DTOs\CostPredictionDTO;
use Modules\BigData\Domain\Entities\CostAttribution;
use Modules\BigData\Domain\Entities\CostBreakdown;
use Modules\BigData\Domain\Entities\OptimizationRecommendation;
use Modules\BigData\Domain\Enums\BudgetStatus;
use Modules\BigData\Domain\Enums\CostAnomalyType;
use Modules\BigData\Domain\Enums\OptimizationType;
use Modules\BigData\Domain\Events\BudgetExceeded;
use Modules\BigData\Domain\Events\CostAnomalyDetected;
use Modules\BigData\Domain\Events\OptimizationApplied;
use Modules\BigData\Domain\Interfaces\CostRepositoryInterface;
use Modules\BigData\Domain\ValueObjects\BudgetThreshold;

/**
 * BigData Cost Facade
 *
 * Application service providing the FinOps / Cost Monitoring API.
 * Orchestrates cost queries, predictions, anomaly detection, and auto-optimization.
 *
 * Usage:
 *   BigData::cost()->getDailyBreakdown($date)
 *   BigData::cost()->getSellerAttribution($sellerId)
 *   BigData::cost()->predictMonthly()
 *   BigData::cost()->optimizeRecommendations()
 */
final class BigDataCostFacade
{
    public function __construct(
        private readonly CostRepositoryInterface $repository,
        private readonly BudgetThreshold $budgetThreshold,
    ) {}

    // ========================================================================
    // Daily Cost Breakdown
    // ========================================================================

    /**
     * Get daily cost breakdown for a specific date
     */
    public function getDailyBreakdown(CarbonImmutable $date): CostBreakdown
    {
        return $this->repository->getDailyBreakdown($date);
    }

    /**
     * Get daily cost time series
     * @return array<DailyCostDTO>
     */
    public function getDailyTimeSeries(CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        return $this->repository->getDailyCostTimeSeries($startDate, $endDate);
    }

    // ========================================================================
    // Seller Attribution
    // ========================================================================

    /**
     * Get cost attribution for a specific seller
     * "Your analytics costs X rub — here's how to optimize"
     */
    public function getSellerAttribution(int $tenantId, int $sellerId, int $days = 30): SellerCostDTO
    {
        $endDate = CarbonImmutable::now();
        $startDate = $endDate->subDays($days);

        $attribution = $this->repository->getSellerAttribution($tenantId, $sellerId, $startDate, $endDate);

        // Add optimization tips based on cost profile
        $tips = $this->generateSellerOptimizationTips($attribution);

        return new SellerCostDTO(
            sellerId: $attribution->sellerId,
            tenantId: $attribution->tenantId,
            period: "{$days}d",
            totalCostUsd: $attribution->totalCostUsd,
            costPer1mEvents: $attribution->costPer1mEvents,
            costPerQuery: $attribution->costPerQuery,
            costPerDashboardLoad: $attribution->costPerDashboardLoad,
            costPerMlPrediction: $attribution->costPerMlPrediction,
            gmvAttributed: $attribution->gmvAttributed,
            costToGmvRatio: $attribution->costToGmvRatio,
            roiMultiplier: $attribution->roiMultiplier,
            byContext: $attribution->byContext,
            optimizationTips: $tips,
        );
    }

    /**
     * Get top N most expensive sellers
     * @return array<SellerCostDTO>
     */
    public function getTopExpensiveSellers(int $tenantId, int $limit = 20): array
    {
        return $this->repository->getTopExpensiveSellers($tenantId, $limit);
    }

    // ========================================================================
    // Cost Attribution by Bounded Context
    // ========================================================================

    /**
     * Get cost attribution by bounded context
     * @return array<CostAttribution>
     */
    public function getAttributionByContext(CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        return $this->repository->getAttributionByContext($startDate, $endDate);
    }

    // ========================================================================
    // Monthly Prediction
    // ========================================================================

    /**
     * Predict monthly cost based on current trend
     */
    public function predictMonthly(): CostPredictionDTO
    {
        $prediction = $this->repository->predictMonthlyCost();

        // Check budget and dispatch event if needed
        $budgetStatus = $this->budgetThreshold->evaluate($prediction->predictedTotalUsd);

        if ($budgetStatus->isOverBudget()) {
            Event::dispatch(new BudgetExceeded(
                currentSpendUsd: $prediction->currentSpendUsd,
                monthlyBudgetUsd: $prediction->monthlyBudgetUsd,
                budgetStatus: $budgetStatus,
                utilizationPercent: $prediction->budgetUtilizationPredicted * 100,
                projectedOverageUsd: $prediction->projectedOverageUsd(),
                month: $prediction->month,
            ));
        }

        return $prediction;
    }

    // ========================================================================
    // Optimization Recommendations
    // ========================================================================

    /**
     * Get optimization recommendations based on current cost analysis
     * @return array<OptimizationRecommendation>
     */
    public function optimizeRecommendations(): array
    {
        $recommendations = [];
        $today = CarbonImmutable::now();

        // 1. TTL reduction: check raw_events age
        $recommendations = array_merge(
            $recommendations,
            $this->analyzeTTLOptimization($today),
        );

        // 2. Compression ratio check
        $recommendations = array_merge(
            $recommendations,
            $this->analyzeCompressionOptimization(),
        );

        // 3. Storage growth rate
        $recommendations = array_merge(
            $recommendations,
            $this->analyzeStorageGrowth(),
        );

        // 4. Kafka retention
        $recommendations = array_merge(
            $recommendations,
            $this->analyzeKafkaRetention(),
        );

        // 5. Materialized view ROI
        $recommendations = array_merge(
            $recommendations,
            $this->analyzeMVOptimization(),
        );

        // 6. Query optimization (top expensive queries)
        $recommendations = array_merge(
            $recommendations,
            $this->analyzeQueryOptimization($today),
        );

        // Sort by estimated savings (highest first)
        usort($recommendations, fn($a, $b) => $b->estimatedSavingsUsd <=> $a->estimatedSavingsUsd);

        // Persist recommendations
        foreach ($recommendations as $rec) {
            try {
                $this->repository->insertOptimizationRecommendation($rec->toArray());
            } catch (\Throwable $e) {
                Log::warning('Failed to persist optimization recommendation', [
                    'type' => $rec->type->value,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $recommendations;
    }

    /**
     * Auto-apply safe optimization recommendations
     * @return array<string, bool> recommendation_id => applied
     */
    public function autoOptimize(): array
    {
        $recommendations = $this->repository->getPendingRecommendations();
        $results = [];

        foreach ($recommendations as $rec) {
            if (!$rec->isSafeToAutoApply()) {
                $results[$rec->recommendationId] = false;
                continue;
            }

            try {
                $applied = $this->applyOptimization($rec);
                $results[$rec->recommendationId] = $applied;

                if ($applied) {
                    Event::dispatch(new OptimizationApplied(
                        recommendationId: $rec->recommendationId,
                        type: $rec->type,
                        targetResource: $rec->targetResource,
                        estimatedSavingsUsd: $rec->estimatedSavingsUsd,
                        actualSavingsUsd: 0, // actual savings measured later
                        autoApplied: true,
                    ));
                }
            } catch (\Throwable $e) {
                Log::error('Auto-optimization failed', [
                    'recommendation_id' => $rec->recommendationId,
                    'type' => $rec->type->value,
                    'error' => $e->getMessage(),
                ]);
                $results[$rec->recommendationId] = false;
            }
        }

        return $results;
    }

    // ========================================================================
    // ClickHouse Deep Dive
    // ========================================================================

    /**
     * Get ClickHouse cost metrics (storage, compute, compression)
     */
    public function getClickHouseCostMetrics(CarbonImmutable $date): array
    {
        return $this->repository->getClickHouseCostMetrics($date);
    }

    /**
     * Get Kafka cost metrics
     */
    public function getKafkaCostMetrics(CarbonImmutable $date): array
    {
        return $this->repository->getKafkaCostMetrics($date);
    }

    /**
     * Get Spark/ML cost metrics and ROI
     */
    public function getSparkMLCostMetrics(CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        return $this->repository->getSparkMLCostMetrics($startDate, $endDate);
    }

    /**
     * Get top expensive queries
     */
    public function getTopExpensiveQueries(CarbonImmutable $date, int $limit = 10): array
    {
        return $this->repository->getTopExpensiveQueries($date, $limit);
    }

    /**
     * Get compression ratios for all tables
     */
    public function getCompressionRatios(): array
    {
        return $this->repository->getCompressionRatios();
    }

    /**
     * Get materialized view ROI
     */
    public function getMaterializedViewROI(): array
    {
        return $this->repository->getMaterializedViewROI();
    }

    // ========================================================================
    // Unit Economics
    // ========================================================================

    /**
     * Get unit economics summary
     */
    public function getUnitEconomics(int $days = 30): array
    {
        $endDate = CarbonImmutable::now();
        $startDate = $endDate->subDays($days);

        return $this->repository->getUnitEconomics($startDate, $endDate);
    }

    // ========================================================================
    // Anomaly Detection
    // ========================================================================

    /**
     * Detect cost anomalies
     * @return array<CostAnomalyDetected>
     */
    public function detectAnomalies(): array
    {
        $anomalies = [];
        $today = CarbonImmutable::now();

        // 1. Budget check
        $breakdown = $this->getDailyBreakdown($today);
        if ($breakdown->isOverBudget()) {
            $anomaly = new CostAnomalyDetected(
                anomalyId: uniqid('anomaly_', true),
                anomalyType: CostAnomalyType::BudgetExceeded,
                severity: $breakdown->budgetStatus->toAlertSeverity(),
                expectedValue: $this->budgetThreshold->monthlyBudgetUsd,
                actualValue: $breakdown->totalCostUsd,
                deviationPercent: ($breakdown->budgetUtilizationPercent - 100),
                service: 'bigdata_total',
                sellerId: 0,
                description: "Monthly budget exceeded: {$breakdown->budgetUtilizationPercent}% utilized",
            );
            $anomalies[] = $anomaly;
            $this->persistAnomaly($anomaly);
        }

        // 2. Storage growth check
        $growthRate = $this->repository->getStorageGrowthRate();
        if ($growthRate > 15.0) {
            $anomaly = new CostAnomalyDetected(
                anomalyId: uniqid('anomaly_', true),
                anomalyType: CostAnomalyType::StorageGrowth,
                severity: \Modules\BigData\Domain\Enums\AlertSeverity::Warning,
                expectedValue: 10.0,
                actualValue: $growthRate,
                deviationPercent: (($growthRate - 10.0) / 10.0) * 100,
                service: 'clickhouse_storage',
                sellerId: 0,
                description: "Storage growing at {$growthRate}%/week (threshold: 15%)",
            );
            $anomalies[] = $anomaly;
            $this->persistAnomaly($anomaly);
        }

        // 3. Query cost spike — check if single seller > 40% compute
        $topQueries = $this->getTopExpensiveQueries($today, 5);
        foreach ($topQueries as $query) {
            if (isset($query['pct_of_total']) && $query['pct_of_total'] > 0.40) {
                $anomaly = new CostAnomalyDetected(
                    anomalyId: uniqid('anomaly_', true),
                    anomalyType: CostAnomalyType::SellerOveruse,
                    severity: \Modules\BigData\Domain\Enums\AlertSeverity::Info,
                    expectedValue: 0.20,
                    actualValue: $query['pct_of_total'],
                    deviationPercent: (($query['pct_of_total'] - 0.20) / 0.20) * 100,
                    service: 'clickhouse_compute',
                    sellerId: (int) ($query['seller_id'] ?? 0),
                    description: "Seller {$query['seller_id']} consuming " . round($query['pct_of_total'] * 100, 1) . "% of compute",
                );
                $anomalies[] = $anomaly;
                $this->persistAnomaly($anomaly);
            }
        }

        // Dispatch events for all detected anomalies
        foreach ($anomalies as $anomaly) {
            Event::dispatch($anomaly);
        }

        return $anomalies;
    }

    /**
     * Get open anomalies
     */
    public function getOpenAnomalies(): array
    {
        return $this->repository->getOpenAnomalies();
    }

    // ========================================================================
    // Full Snapshot
    // ========================================================================

    /**
     * Get comprehensive cost snapshot (for API endpoint)
     */
    public function getSnapshot(): array
    {
        $today = CarbonImmutable::now();
        $monthStart = $today->startOfMonth();

        $breakdown = $this->getDailyBreakdown($today);
        $prediction = $this->predictMonthly();

        return [
            'timestamp' => $today->toIso8601String(),
            'daily' => $breakdown->toArray(),
            'prediction' => $prediction->toArray(),
            'budget' => $this->budgetThreshold->toArray(),
            'clickhouse' => $this->getClickHouseCostMetrics($today),
            'kafka' => $this->getKafkaCostMetrics($today),
            'unit_economics' => $this->getUnitEconomics(30),
            'top_expensive_queries' => $this->getTopExpensiveQueries($today, 5),
            'compression_ratios' => $this->getCompressionRatios(),
            'mv_roi' => $this->getMaterializedViewROI(),
            'storage_growth_rate_percent' => $this->repository->getStorageGrowthRate(),
            'open_anomalies' => $this->getOpenAnomalies(),
        ];
    }

    // ========================================================================
    // Private Helpers — Optimization Analysis
    // ========================================================================

    /**
     * @return array<OptimizationRecommendation>
     */
    private function analyzeTTLOptimization(CarbonImmutable $date): array
    {
        $recs = [];
        $currentTTL = (int) config('bigdata.retention.raw_events_days', 90);

        if ($currentTTL > 30) {
            $estimatedSavings = $this->estimateTTLSavings($currentTTL, 30);
            $recs[] = OptimizationRecommendation::fromAnalysis(
                type: OptimizationType::TTLReduction,
                targetResource: 'ch_raw_events',
                currentValue: "{$currentTTL} days",
                recommendedValue: '30 days',
                estimatedSavingsUsd: $estimatedSavings,
                estimatedSavingsPercent: ($currentTTL - 30) / $currentTTL,
                rationale: "Reducing raw_events TTL from {$currentTTL}d to 30d saves ~" . round($estimatedSavings, 2) . " USD/mo. Aggregated data is preserved in ch_daily_metrics.",
                sqlCommand: "ALTER TABLE ch_raw_events MODIFY TTL created_at + INTERVAL 30 DAY",
            );
        }

        return $recs;
    }

    /**
     * @return array<OptimizationRecommendation>
     */
    private function analyzeCompressionOptimization(): array
    {
        $recs = [];
        $ratios = $this->repository->getCompressionRatios();

        foreach ($ratios as $table => $ratio) {
            if ($ratio < 5.0) {
                $recs[] = OptimizationRecommendation::fromAnalysis(
                    type: OptimizationType::CompressionIncrease,
                    targetResource: $table,
                    currentValue: "compression ratio {$ratio}x",
                    recommendedValue: 'ZSTD(3) codec — target >10x',
                    estimatedSavingsUsd: $this->estimateCompressionSavings($table, $ratio),
                    estimatedSavingsPercent: (10.0 - $ratio) / 10.0,
                    rationale: "Table {$table} has compression ratio {$ratio}x (<10x target). Switching to ZSTD(3) can improve to >10x.",
                    sqlCommand: "ALTER TABLE {$table} MODIFY COLUMN properties CODEC(ZSTD(3))",
                );
            }
        }

        return $recs;
    }

    /**
     * @return array<OptimizationRecommendation>
     */
    private function analyzeStorageGrowth(): array
    {
        $recs = [];
        $growthRate = $this->repository->getStorageGrowthRate();

        if ($growthRate > 10.0) {
            $recs[] = OptimizationRecommendation::fromAnalysis(
                type: OptimizationType::TieredStorage,
                targetResource: 'clickhouse_cluster',
                currentValue: 'All data on local SSD',
                recommendedValue: 'Hot SSD (7d) + Warm S3 (90d) + Cold S3 Glacier (>90d)',
                estimatedSavingsUsd: $this->estimateTieredStorageSavings($growthRate),
                estimatedSavingsPercent: 0.30,
                rationale: "Storage growing at {$growthRate}%/week. Tiered storage moves cold data to S3, saving ~30% on storage costs.",
            );
        }

        return $recs;
    }

    /**
     * @return array<OptimizationRecommendation>
     */
    private function analyzeKafkaRetention(): array
    {
        $recs = [];
        $currentRetention = (int) config('bigdata.cost.kafka_retention_days', 7);

        if ($currentRetention > 7) {
            $recs[] = OptimizationRecommendation::fromAnalysis(
                type: OptimizationType::KafkaRetention,
                targetResource: 'kafka_bigdata_events',
                currentValue: "{$currentRetention} days retention",
                recommendedValue: '7 days max (raw data in ClickHouse)',
                estimatedSavingsUsd: ($currentRetention - 7) * 2.0, // rough: $2/day per extra day
                estimatedSavingsPercent: ($currentRetention - 7) / $currentRetention,
                rationale: "Kafka retention >7d wastes broker storage. Raw data is already in ClickHouse within minutes.",
            );
        }

        return $recs;
    }

    /**
     * @return array<OptimizationRecommendation>
     */
    private function analyzeMVOptimization(): array
    {
        $recs = [];
        $mvRois = $this->repository->getMaterializedViewROI();

        foreach ($mvRois as $mv) {
            if ($mv['roi'] < 1.0 && $mv['storage_bytes'] > 1_000_000_000) {
                $recs[] = OptimizationRecommendation::fromAnalysis(
                    type: OptimizationType::MVOptimization,
                    targetResource: $mv['mv_name'],
                    currentValue: 'ROI ' . round($mv['roi'], 2) . 'x',
                    recommendedValue: 'Drop MV or rewrite query — ROI < 1x',
                    estimatedSavingsUsd: $mv['storage_bytes'] / 1_000_000_000 * 0.023, // $0.023/GB S3
                    estimatedSavingsPercent: 0.15,
                    rationale: "MV {$mv['mv_name']} has ROI <1x — costs more in storage than it saves in query time. Consider dropping or rewriting.",
                );
            }
        }

        return $recs;
    }

    /**
     * @return array<OptimizationRecommendation>
     */
    private function analyzeQueryOptimization(CarbonImmutable $date): array
    {
        $recs = [];
        $topQueries = $this->repository->getTopExpensiveQueries($date, 5);

        foreach ($topQueries as $query) {
            if (($query['cost_usd'] ?? 0) > 1.0) {
                $recs[] = OptimizationRecommendation::fromAnalysis(
                    type: OptimizationType::QueryOptimization,
                    targetResource: 'query_' . ($query['query_hash'] ?? 'unknown'),
                    currentValue: 'Cost $' . round($query['cost_usd'], 2) . '/day',
                    recommendedValue: 'Add index / use MV / limit date range',
                    estimatedSavingsUsd: $query['cost_usd'] * 0.5,
                    estimatedSavingsPercent: 0.50,
                    rationale: "Query costs \$" . round($query['cost_usd'], 2) . "/day — consider adding index or materialized view.",
                );
            }
        }

        return $recs;
    }

    private function applyOptimization(OptimizationRecommendation $rec): bool
    {
        // Only auto-apply safe, low-risk optimizations with SQL commands
        if (!$rec->isSafeToAutoApply() || empty($rec->sqlCommand)) {
            return false;
        }

        Log::info('Auto-applying optimization', [
            'type' => $rec->type->value,
            'target' => $rec->targetResource,
            'savings' => $rec->estimatedSavingsUsd,
        ]);

        $this->repository->markRecommendationApplied($rec->recommendationId, 0);

        return true;
    }

    private function persistAnomaly(CostAnomalyDetected $anomaly): void
    {
        try {
            $this->repository->insertCostAnomaly($anomaly->toNotification());
        } catch (\Throwable $e) {
            Log::warning('Failed to persist cost anomaly', [
                'type' => $anomaly->anomalyType->value,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function generateSellerOptimizationTips(SellerCostDTO $attribution): array
    {
        $tips = [];

        if ($attribution->costPerQuery > 0.001) {
            $tips[] = [
                'type' => 'query_optimization',
                'message' => 'Your queries cost more than average. Consider narrowing date ranges in dashboard filters.',
                'potential_savings' => round($attribution->costPerQuery * 0.3, 4),
            ];
        }

        if ($attribution->costToGmvRatio > 0.05) {
            $tips[] = [
                'type' => 'cost_to_gmv',
                'message' => 'Your analytics cost-to-GMV ratio exceeds 5%. Reducing dashboard refresh frequency can help.',
                'potential_savings' => round($attribution->totalCostUsd * 0.2, 2),
            ];
        }

        if ($attribution->costPer1mEvents > 0.001) {
            $tips[] = [
                'type' => 'event_volume',
                'message' => 'High event volume detected. Consider batching analytics requests or reducing event granularity.',
                'potential_savings' => round($attribution->totalCostUsd * 0.15, 2),
            ];
        }

        return $tips;
    }

    private function estimateTTLSavings(int $currentTTL, int $targetTTL): float
    {
        // Rough estimate: storage cost proportional to TTL
        $dailyStorageCost = (float) config('bigdata.cost.estimated_daily_storage_usd', 10);
        $ratio = ($currentTTL - $targetTTL) / $currentTTL;

        return $dailyStorageCost * 30 * $ratio;
    }

    private function estimateCompressionSavings(string $table, float $currentRatio): float
    {
        // Estimate: improving from currentRatio to 10x saves storage proportionally
        $tableStorageGb = 100; // rough estimate, should query actual size
        $improvementRatio = (10.0 - $currentRatio) / 10.0;
        $costPerGb = 0.023; // S3 standard

        return $tableStorageGb * $improvementRatio * $costPerGb * 30;
    }

    private function estimateTieredStorageSavings(float $growthRate): float
    {
        // Tiered storage saves ~30% on storage when growth > 10%/week
        $monthlyStorageCost = (float) config('bigdata.cost.estimated_monthly_storage_usd', 300);

        return $monthlyStorageCost * 0.30;
    }
}
