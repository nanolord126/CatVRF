<?php

declare(strict_types=1);

namespace Modules\BigData\Infrastructure\Repositories;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\BigData\Domain\DTOs\CostPredictionDTO;
use Modules\BigData\Domain\DTOs\DailyCostDTO;
use Modules\BigData\Domain\DTOs\SellerCostDTO;
use Modules\BigData\Domain\Entities\CostAttribution;
use Modules\BigData\Domain\Entities\CostBreakdown;
use Modules\BigData\Domain\Entities\OptimizationRecommendation;
use Modules\BigData\Domain\Enums\BoundedContext;
use Modules\BigData\Domain\Enums\BudgetStatus;
use Modules\BigData\Domain\Enums\CloudProvider;
use Modules\BigData\Domain\Enums\CostCategory;
use Modules\BigData\Domain\Interfaces\CostRepositoryInterface;
use Modules\BigData\Domain\ValueObjects\BudgetThreshold;
use Modules\BigData\Infrastructure\ClickHouse\ClickHouseClient;

/**
 * ClickHouse Cost Repository
 *
 * Infrastructure implementation of CostRepositoryInterface.
 * All queries go through ClickHouseClient (HTTP-based).
 * Results are cached with proper tags for invalidation.
 */
final class ClickHouseCostRepository implements CostRepositoryInterface
{
    private const CACHE_TTL = 1800; // 30 min
    private const CACHE_TAG = 'bigdata-cost';

    public function __construct(
        private readonly ClickHouseClient $client,
        private readonly BudgetThreshold $budgetThreshold,
    ) {}

    // ========================================================================
    // Daily Breakdown
    // ========================================================================

    public function getDailyBreakdown(CarbonImmutable $date): CostBreakdown
    {
        $cacheKey = "bigdata:cost:daily:{$date->toDateString()}";

        return Cache::tags([self::CACHE_TAG])->remember($cacheKey, self::CACHE_TTL, function () use ($date) {
            $query = <<<'SQL'
                SELECT
                    cost_date,
                    cloud_provider,
                    service,
                    cost_category,
                    sum(total_cost_usd) AS total_cost_usd,
                    sum(total_cost_rub) AS total_cost_rub,
                    sum(total_optimizable_cost) AS total_optimizable_cost,
                    sum(total_optimization_potential) AS total_optimization_potential
                FROM ch_cost_aggregated_daily
                WHERE cost_date = :date
                GROUP BY cost_date, cloud_provider, service, cost_category
                SQL;

            $rows = $this->client->select($query, ['date' => $date->toDateString()]);

            if (empty($rows)) {
                return CostBreakdown::unknown($date->toDateTimeImmutable());
            }

            $totalCostUsd = 0;
            $totalCostRub = 0;
            $byCategory = [];
            $byService = [];
            $byProvider = [];
            $optimizableCost = 0;
            $optimizationPotential = 0;

            foreach ($rows as $row) {
                $cost = (float) ($row['total_cost_usd'] ?? 0);
                $totalCostUsd += $cost;
                $totalCostRub += (float) ($row['total_cost_rub'] ?? 0);
                $optimizableCost += (float) ($row['total_optimizable_cost'] ?? 0);
                $optimizationPotential += (float) ($row['total_optimization_potential'] ?? 0);

                $category = $row['cost_category'] ?? 'other';
                $byCategory[$category] = ($byCategory[$category] ?? 0) + $cost;

                $service = $row['service'] ?? 'unknown';
                $byService[$service] = ($byService[$service] ?? 0) + $cost;

                $provider = $row['cloud_provider'] ?? 'self_hosted';
                $byProvider[$provider] = ($byProvider[$provider] ?? 0) + $cost;
            }

            return CostBreakdown::fromMetrics(
                date: $date->toDateTimeImmutable(),
                totalCostUsd: $totalCostUsd,
                totalCostRub: $totalCostRub,
                byCategory: $byCategory,
                byService: $byService,
                byProvider: $byProvider,
                optimizableCostUsd: $optimizableCost,
                optimizationPotentialUsd: $optimizationPotential,
                monthlyBudgetUsd: $this->budgetThreshold->monthlyBudgetUsd,
            );
        });
    }

    public function getDailyCostTimeSeries(CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        $cacheKey = "bigdata:cost:timeseries:{$startDate->toDateString()}:{$endDate->toDateString()}";

        return Cache::tags([self::CACHE_TAG])->remember($cacheKey, self::CACHE_TTL, function () use ($startDate, $endDate) {
            $query = <<<'SQL'
                SELECT
                    cost_date,
                    sumIf(total_cost_usd, cost_category = 'compute') AS compute_cost_usd,
                    sumIf(total_cost_usd, cost_category = 'storage') AS storage_cost_usd,
                    sumIf(total_cost_usd, cost_category = 'network') AS network_cost_usd,
                    sumIf(total_cost_usd, cost_category = 'license') AS license_cost_usd,
                    sumIf(total_cost_usd, cost_category IN ('support', 'other')) AS other_cost_usd,
                    sum(total_cost_usd) AS total_cost_usd,
                    sum(total_optimizable_cost) AS total_optimizable_cost,
                    sum(total_optimization_potential) AS total_optimization_potential
                FROM ch_cost_aggregated_daily
                WHERE cost_date BETWEEN :start_date AND :end_date
                GROUP BY cost_date
                ORDER BY cost_date
                SQL;

            $rows = $this->client->select($query, [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ]);

            return array_map(fn(array $row) => DailyCostDTO::fromArray($row), $rows);
        });
    }

    // ========================================================================
    // Seller Attribution
    // ========================================================================

    public function getSellerAttribution(int $tenantId, int $sellerId, CarbonImmutable $startDate, CarbonImmutable $endDate): SellerCostDTO
    {
        $cacheKey = "bigdata:cost:seller:{$tenantId}:{$sellerId}:{$startDate->toDateString()}:{$endDate->toDateString()}";

        return Cache::tags([self::CACHE_TAG, "seller:{$sellerId}"])->remember($cacheKey, self::CACHE_TTL, function () use ($tenantId, $sellerId, $startDate, $endDate) {
            $query = <<<'SQL'
                SELECT
                    seller_id,
                    tenant_id,
                    sum(attributed_cost_usd) AS total_cost_usd,
                    sum(events_processed) AS events_processed,
                    sum(queries_executed) AS queries_executed,
                    sum(dashboard_loads) AS dashboard_loads,
                    sum(ml_predictions) AS ml_predictions,
                    if(sum(events_processed) > 0, sum(attributed_cost_usd) * 1e6 / sum(events_processed), 0) AS cost_per_1m_events,
                    if(sum(queries_executed) > 0, sum(attributed_cost_usd) / sum(queries_executed), 0) AS cost_per_query,
                    if(sum(dashboard_loads) > 0, sum(attributed_cost_usd) / sum(dashboard_loads), 0) AS cost_per_dashboard_load,
                    if(sum(ml_predictions) > 0, sum(attributed_cost_usd) / sum(ml_predictions), 0) AS cost_per_ml_prediction,
                    sum(gmv_attributed) AS gmv_attributed,
                    sum(revenue_attributed) AS revenue_attributed,
                    if(sum(gmv_attributed) > 0, sum(attributed_cost_usd) / sum(gmv_attributed), 0) AS cost_to_gmv_ratio,
                    if(sum(attributed_cost_usd) > 0, sum(revenue_attributed) / sum(attributed_cost_usd), 0) AS roi_multiplier
                FROM ch_cost_attribution
                WHERE tenant_id = :tenant_id
                    AND seller_id = :seller_id
                    AND attribution_date BETWEEN :start_date AND :end_date
                GROUP BY seller_id, tenant_id
                SQL;

            $row = $this->client->selectOne($query, [
                'tenant_id' => $tenantId,
                'seller_id' => $sellerId,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ]);

            if ($row === null) {
                return new SellerCostDTO(
                    sellerId: $sellerId,
                    tenantId: $tenantId,
                    period: '30d',
                    totalCostUsd: 0,
                    costPer1mEvents: 0,
                    costPerQuery: 0,
                    costPerDashboardLoad: 0,
                    costPerMlPrediction: 0,
                    gmvAttributed: 0,
                    costToGmvRatio: 0,
                    roiMultiplier: 0,
                    byContext: [],
                    optimizationTips: [],
                );
            }

            return SellerCostDTO::fromArray(array_merge($row, ['period' => '30d', 'by_context' => [], 'optimization_tips' => []]));
        });
    }

    public function getAttributionByContext(CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        $cacheKey = "bigdata:cost:context:{$startDate->toDateString()}:{$endDate->toDateString()}";

        return Cache::tags([self::CACHE_TAG])->remember($cacheKey, self::CACHE_TTL, function () use ($startDate, $endDate) {
            $query = <<<'SQL'
                SELECT
                    attribution_date,
                    tenant_id,
                    seller_id,
                    bounded_context,
                    vertical,
                    sum(attributed_cost_usd) AS attributed_cost_usd,
                    sum(events_processed) AS events_processed,
                    sum(queries_executed) AS queries_executed,
                    sum(dashboard_loads) AS dashboard_loads,
                    sum(ml_predictions) AS ml_predictions,
                    sum(gmv_attributed) AS gmv_attributed,
                    sum(revenue_attributed) AS revenue_attributed
                FROM ch_cost_attribution
                WHERE attribution_date BETWEEN :start_date AND :end_date
                GROUP BY attribution_date, tenant_id, seller_id, bounded_context, vertical
                ORDER BY attributed_cost_usd DESC
                SQL;

            $rows = $this->client->select($query, [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ]);

            return array_map(function (array $row) {
                return CostAttribution::fromMetrics(
                    date: CarbonImmutable::parse($row['attribution_date'])->toDateTimeImmutable(),
                    tenantId: (int) $row['tenant_id'],
                    sellerId: (int) $row['seller_id'],
                    boundedContext: BoundedContext::from($row['bounded_context']),
                    vertical: $row['vertical'] ?? '',
                    attributedCostUsd: (float) $row['attributed_cost_usd'],
                    eventsProcessed: (int) $row['events_processed'],
                    queriesExecuted: (int) $row['queries_executed'],
                    dashboardLoads: (int) $row['dashboard_loads'],
                    mlPredictions: (int) $row['ml_predictions'],
                    gmvAttributed: (float) $row['gmv_attributed'],
                    revenueAttributed: (float) $row['revenue_attributed'],
                    ordersAnalyzed: (int) $row['events_processed'],
                );
            }, $rows);
        });
    }

    public function getTopExpensiveSellers(int $tenantId, int $limit = 20): array
    {
        $cacheKey = "bigdata:cost:top_sellers:{$tenantId}:{$limit}";

        return Cache::tags([self::CACHE_TAG])->remember($cacheKey, self::CACHE_TTL, function () use ($tenantId, $limit) {
            $query = <<<'SQL'
                SELECT
                    seller_id,
                    tenant_id,
                    sum(attributed_cost_usd) AS total_cost_usd,
                    sum(gmv_attributed) AS gmv_attributed,
                    if(sum(gmv_attributed) > 0, sum(attributed_cost_usd) / sum(gmv_attributed), 0) AS cost_to_gmv_ratio
                FROM ch_cost_attribution
                WHERE tenant_id = :tenant_id
                    AND attribution_date >= today() - 30
                GROUP BY seller_id, tenant_id
                ORDER BY total_cost_usd DESC
                LIMIT :limit
                SQL;

            $rows = $this->client->select($query, [
                'tenant_id' => $tenantId,
                'limit' => $limit,
            ]);

            return array_map(fn(array $row) => SellerCostDTO::fromArray(array_merge($row, [
                'period' => '30d',
                'cost_per_1m_events' => 0,
                'cost_per_query' => 0,
                'cost_per_dashboard_load' => 0,
                'cost_per_ml_prediction' => 0,
                'roi_multiplier' => 0,
                'by_context' => [],
                'optimization_tips' => [],
            ])), $rows);
        });
    }

    // ========================================================================
    // Prediction
    // ========================================================================

    public function predictMonthlyCost(): CostPredictionDTO
    {
        $cacheKey = "bigdata:cost:prediction:" . now()->format('Y-m');

        return Cache::tags([self::CACHE_TAG])->remember($cacheKey, 900, function () {
            $monthStart = CarbonImmutable::now()->startOfMonth();
            $today = CarbonImmutable::now();
            $daysElapsed = $monthStart->diffInDays($today) + 1;
            $daysInMonth = $today->daysInMonth;
            $daysRemaining = $daysInMonth - $daysElapsed;

            $query = <<<'SQL'
                SELECT
                    sum(total_cost_usd) AS current_spend,
                    avg(daily_total) AS daily_average,
                    any(trend_pct) AS trend_percent
                FROM (
                    SELECT
                        cost_date,
                        sum(total_cost_usd) AS daily_total,
                        sum(total_cost_usd) AS total_cost_usd,
                        0 AS trend_pct
                    FROM ch_cost_aggregated_daily
                    WHERE cost_date >= :month_start
                    GROUP BY cost_date
                )
                SQL;

            $row = $this->client->selectOne($query, [
                'month_start' => $monthStart->toDateString(),
            ]);

            $currentSpend = (float) ($row['current_spend'] ?? 0);
            $dailyAverage = $daysElapsed > 0 ? $currentSpend / $daysElapsed : 0;
            $trendPercent = (float) ($row['trend_percent'] ?? 0);
            $predictedTotal = $dailyAverage * $daysInMonth;
            $monthlyBudget = $this->budgetThreshold->monthlyBudgetUsd;
            $budgetUtilization = $monthlyBudget > 0 ? $predictedTotal / $monthlyBudget : 0;

            return new CostPredictionDTO(
                month: $today->format('Y-m'),
                currentSpendUsd: $currentSpend,
                predictedTotalUsd: $predictedTotal,
                monthlyBudgetUsd: $monthlyBudget,
                budgetUtilizationPredicted: $budgetUtilization,
                dailyAverageUsd: $dailyAverage,
                daysRemaining: max(0, $daysRemaining),
                trendPercent: $trendPercent,
                byServicePrediction: [],
            );
        });
    }

    // ========================================================================
    // ClickHouse / Kafka / Spark Deep Dive
    // ========================================================================

    public function getClickHouseCostMetrics(CarbonImmutable $date): array
    {
        $cacheKey = "bigdata:cost:ch_metrics:{$date->toDateString()}";

        return Cache::tags([self::CACHE_TAG])->remember($cacheKey, self::CACHE_TTL, function () use ($date) {
            $query = <<<'SQL'
                SELECT *
                FROM ch_clickhouse_cost_metrics
                WHERE metric_date = :date
                ORDER BY metric_hour DESC
                LIMIT 1
                SQL;

            $row = $this->client->selectOne($query, ['date' => $date->toDateString()]);

            return $row ?: [
                'metric_date' => $date->toDateString(),
                'compression_ratio' => 0,
                'estimated_storage_cost_usd' => 0,
                'estimated_compute_cost_usd' => 0,
                'estimated_total_cost_usd' => 0,
                'query_count' => 0,
                'merge_count' => 0,
                'mv_roi' => 0,
            ];
        });
    }

    public function getKafkaCostMetrics(CarbonImmutable $date): array
    {
        $cacheKey = "bigdata:cost:kafka_metrics:{$date->toDateString()}";

        return Cache::tags([self::CACHE_TAG])->remember($cacheKey, self::CACHE_TTL, function () use ($date) {
            $query = <<<'SQL'
                SELECT *
                FROM ch_kafka_cost_metrics
                WHERE metric_date = :date
                ORDER BY metric_hour DESC
                LIMIT 1
                SQL;

            return $this->client->selectOne($query, ['date' => $date->toDateString()]) ?: [];
        });
    }

    public function getSparkMLCostMetrics(CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        $query = <<<'SQL'
            SELECT
                job_type,
                job_name,
                sum(total_cost_usd) AS total_cost_usd,
                sum(duration_seconds) AS total_duration_seconds,
                avg(cluster_size) AS avg_cluster_size,
                sum(gmv_uplift) AS total_gmv_uplift,
                sum(revenue_uplift) AS total_revenue_uplift,
                if(sum(total_cost_usd) > 0, sum(revenue_uplift) / sum(total_cost_usd), 0) AS roi_multiplier
            FROM ch_spark_ml_cost_metrics
            WHERE metric_date BETWEEN :start_date AND :end_date
            GROUP BY job_type, job_name
            ORDER BY total_cost_usd DESC
            SQL;

        return $this->client->select($query, [
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
        ]);
    }

    public function getTopExpensiveQueries(CarbonImmutable $date, int $limit = 10): array
    {
        $cacheKey = "bigdata:cost:top_queries:{$date->toDateString()}:{$limit}";

        return Cache::tags([self::CACHE_TAG])->remember($cacheKey, self::CACHE_TTL, function () use ($date, $limit) {
            $query = <<<'SQL'
                SELECT
                    query_hash,
                    query,
                    cost_usd,
                    rows_read,
                    bytes_read,
                    duration_ms,
                    seller_id,
                    cost_usd / nullIf(sum(cost_usd) OVER (), 0) AS pct_of_total
                FROM system.query_log
                WHERE event_date = :date
                    AND type = 'QueryFinish'
                    AND query NOT LIKE '%system%'
                    AND query NOT LIKE '%INFORMATION_SCHEMA%'
                ORDER BY cost_usd DESC
                LIMIT :limit
                SQL;

            return $this->client->select($query, [
                'date' => $date->toDateString(),
                'limit' => $limit,
            ]);
        });
    }

    public function getCompressionRatios(): array
    {
        $cacheKey = "bigdata:cost:compression_ratios";

        return Cache::tags([self::CACHE_TAG])->remember($cacheKey, self::CACHE_TTL, function () {
            $query = <<<'SQL'
                SELECT
                    table,
                    sum(data_compressed_bytes) AS compressed,
                    sum(data_uncompressed_bytes) AS uncompressed,
                    if(sum(data_compressed_bytes) > 0, sum(data_uncompressed_bytes) / sum(data_compressed_bytes), 0) AS ratio
                FROM system.columns
                WHERE database = currentDatabase()
                    AND table LIKE 'ch_%'
                GROUP BY table
                ORDER BY uncompressed DESC
                SQL;

            $rows = $this->client->select($query);
            $ratios = [];
            foreach ($rows as $row) {
                $ratios[$row['table']] = (float) $row['ratio'];
            }

            return $ratios;
        });
    }

    public function getStorageGrowthRate(): float
    {
        $cacheKey = "bigdata:cost:storage_growth";

        return Cache::tags([self::CACHE_TAG])->remember($cacheKey, self::CACHE_TTL, function () {
            $query = <<<'SQL'
                SELECT
                    (last_week - prev_week) / nullIf(prev_week, 0) * 100 AS growth_rate_percent
                FROM (
                    SELECT
                        (SELECT sum(total_disk_bytes) FROM ch_clickhouse_cost_metrics
                         WHERE metric_date >= today() - 7) AS last_week,
                        (SELECT sum(total_disk_bytes) FROM ch_clickhouse_cost_metrics
                         WHERE metric_date >= today() - 14 AND metric_date < today() - 7) AS prev_week
                )
                SQL;

            $row = $this->client->selectOne($query);

            return (float) ($row['growth_rate_percent'] ?? 0);
        });
    }

    public function getMaterializedViewROI(): array
    {
        $cacheKey = "bigdata:cost:mv_roi";

        return Cache::tags([self::CACHE_TAG])->remember($cacheKey, self::CACHE_TTL, function () {
            $query = <<<'SQL'
                SELECT
                    name AS mv_name,
                    sum(data_compressed_bytes) AS storage_bytes,
                    0 AS savings_usd,
                    0 AS roi
                FROM system.tables
                WHERE name LIKE '%_mv'
                    AND database = currentDatabase()
                GROUP BY name
                SQL;

            return $this->client->select($query);
        });
    }

    // ========================================================================
    // Write Operations
    // ========================================================================

    public function insertBillingRaw(array $records): int
    {
        if (empty($records)) {
            return 0;
        }

        return $this->client->insertJsonEachRow('ch_billing_raw', $records);
    }

    public function insertCostAttribution(array $records): int
    {
        if (empty($records)) {
            return 0;
        }

        return $this->client->insertJsonEachRow('ch_cost_attribution', $records);
    }

    public function insertCostAnomaly(array $anomaly): void
    {
        $this->client->insertJsonEachRow('ch_cost_anomalies', [$anomaly]);
    }

    public function insertOptimizationRecommendation(array $recommendation): void
    {
        $this->client->insertJsonEachRow('ch_cost_optimization_recommendations', [$recommendation]);
    }

    public function getOpenAnomalies(): array
    {
        $query = <<<'SQL'
            SELECT *
            FROM ch_cost_anomalies
            WHERE status IN ('open', 'investigating')
            ORDER BY detected_at DESC
            LIMIT 50
            SQL;

        return $this->client->select($query);
    }

    public function getPendingRecommendations(): array
    {
        $query = <<<'SQL'
            SELECT *
            FROM ch_cost_optimization_recommendations
            WHERE status = 'pending'
            ORDER BY estimated_savings_usd DESC
            LIMIT 50
            SQL;

        $rows = $this->client->select($query);

        return array_map(function (array $row) {
            return OptimizationRecommendation::fromAnalysis(
                type: \Modules\BigData\Domain\Enums\OptimizationType::from($row['optimization_type'] ?? 'ttl_reduction'),
                targetResource: $row['target_resource'] ?? '',
                currentValue: $row['current_value'] ?? '',
                recommendedValue: $row['recommended_value'] ?? '',
                estimatedSavingsUsd: (float) ($row['estimated_savings_usd'] ?? 0),
                estimatedSavingsPercent: (float) ($row['estimated_savings_percent'] ?? 0),
                rationale: $row['rationale'] ?? '',
                sqlCommand: $row['sql_command'] ?? '',
            );
        }, $rows);
    }

    public function markRecommendationApplied(string $recommendationId, float $actualSavingsUsd): void
    {
        $query = <<<'SQL'
            ALTER TABLE ch_cost_optimization_recommendations
            UPDATE status = 'applied', actual_savings_usd = :savings, applied_at = now()
            WHERE recommendation_id = :id
            SQL;

        $this->client->execute($query, [
            'id' => $recommendationId,
            'savings' => $actualSavingsUsd,
        ]);
    }

    public function getUnitEconomics(CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        $cacheKey = "bigdata:cost:unit_economics:{$startDate->toDateString()}:{$endDate->toDateString()}";

        return Cache::tags([self::CACHE_TAG])->remember($cacheKey, self::CACHE_TTL, function () use ($startDate, $endDate) {
            $query = <<<'SQL'
                SELECT
                    bounded_context,
                    sum(attributed_cost_usd) AS total_cost_usd,
                    sum(events_processed) AS total_events,
                    sum(queries_executed) AS total_queries,
                    sum(dashboard_loads) AS total_dashboard_loads,
                    sum(ml_predictions) AS total_ml_predictions,
                    sum(gmv_attributed) AS total_gmv,
                    sum(revenue_attributed) AS total_revenue,
                    if(sum(events_processed) > 0, sum(attributed_cost_usd) * 1e6 / sum(events_processed), 0) AS cost_per_1m_events,
                    if(sum(queries_executed) > 0, sum(attributed_cost_usd) / sum(queries_executed), 0) AS cost_per_query,
                    if(sum(dashboard_loads) > 0, sum(attributed_cost_usd) / sum(dashboard_loads), 0) AS cost_per_dashboard_load,
                    if(sum(ml_predictions) > 0, sum(attributed_cost_usd) / sum(ml_predictions), 0) AS cost_per_ml_prediction,
                    if(sum(gmv_attributed) > 0, sum(attributed_cost_usd) / sum(gmv_attributed), 0) AS cost_to_gmv_ratio,
                    if(sum(attributed_cost_usd) > 0, sum(revenue_attributed) / sum(attributed_cost_usd), 0) AS roi_multiplier
                FROM ch_cost_attribution
                WHERE attribution_date BETWEEN :start_date AND :end_date
                GROUP BY bounded_context
                ORDER BY total_cost_usd DESC
                SQL;

            return $this->client->select($query, [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ]);
        });
    }
}
