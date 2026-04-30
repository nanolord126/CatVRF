<?php

declare(strict_types=1);

namespace Modules\BigData\Infrastructure\Exporters;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Modules\BigData\Domain\Interfaces\CostRepositoryInterface;

/**
 * BigData Cost Prometheus Exporter
 *
 * Renders cost metrics in Prometheus text exposition format.
 * Metric prefix: catvrf_bigdata_cost_
 *
 * Scraped by Prometheus at /metrics/bigdata-cost
 */
final class BigDataCostExporter
{
    private const METRIC_PREFIX = 'catvrf_bigdata_cost_';

    public function __construct(
        private readonly CostRepositoryInterface $repository,
    ) {}

    public function render(): string
    {
        $lines = [];

        try {
            $lines = array_merge(
                $lines,
                $this->renderDailyCostMetrics(),
                $this->renderBudgetMetrics(),
                $this->renderClickHouseCostMetrics(),
                $this->renderKafkaCostMetrics(),
                $this->renderUnitEconomicsMetrics(),
                $this->renderCompressionMetrics(),
                $this->renderStorageGrowthMetrics(),
                $this->renderAnomalyMetrics(),
            );
        } catch (\Throwable $e) {
            Log::error('BigData cost exporter: render failed', ['error' => $e->getMessage()]);
            $lines[] = '# Export failed: ' . $e->getMessage();
        }

        return implode("\n", $lines) . "\n";
    }

    public function getContentType(): string
    {
        return 'text/plain; version=0.0.4; charset=utf-8';
    }

    // ========================================================================
    // Daily Cost
    // ========================================================================

    private function renderDailyCostMetrics(): array
    {
        $prefix = self::METRIC_PREFIX . 'daily_';
        $lines = [];

        $today = CarbonImmutable::now();
        $breakdown = $this->repository->getDailyBreakdown($today);

        $lines[] = "# HELP {$prefix}total_usd Total daily cost in USD";
        $lines[] = "# TYPE {$prefix}total_usd gauge";
        $lines[] = "{$prefix}total_usd " . round($breakdown->totalCostUsd, 4);

        $lines[] = "# HELP {$prefix}by_category_usd Daily cost by category in USD";
        $lines[] = "# TYPE {$prefix}by_category_usd gauge";
        foreach ($breakdown->byCategory as $category => $cost) {
            $lines[] = "{$prefix}by_category_usd{category=\"{$category}\"} " . round($cost, 4);
        }

        $lines[] = "# HELP {$prefix}optimizable_usd Optimizable portion of daily cost";
        $lines[] = "# TYPE {$prefix}optimizable_usd gauge";
        $lines[] = "{$prefix}optimizable_usd " . round($breakdown->optimizableCostUsd, 4);

        $lines[] = "# HELP {$prefix}optimization_potential_usd Estimated optimization potential in USD";
        $lines[] = "# TYPE {$prefix}optimization_potential_usd gauge";
        $lines[] = "{$prefix}optimization_potential_usd " . round($breakdown->optimizationPotentialUsd, 4);

        return $lines;
    }

    // ========================================================================
    // Budget
    // ========================================================================

    private function renderBudgetMetrics(): array
    {
        $prefix = self::METRIC_PREFIX . 'budget_';
        $lines = [];

        $prediction = $this->repository->predictMonthlyCost();

        $lines[] = "# HELP {$prefix}monthly_usd Monthly budget in USD";
        $lines[] = "# TYPE {$prefix}monthly_usd gauge";
        $lines[] = "{$prefix}monthly_usd " . round($prediction->monthlyBudgetUsd, 2);

        $lines[] = "# HELP {$prefix}current_spend_usd Current month spend in USD";
        $lines[] = "# TYPE {$prefix}current_spend_usd gauge";
        $lines[] = "{$prefix}current_spend_usd " . round($prediction->currentSpendUsd, 2);

        $lines[] = "# HELP {$prefix}predicted_total_usd Predicted monthly total in USD";
        $lines[] = "# TYPE {$prefix}predicted_total_usd gauge";
        $lines[] = "{$prefix}predicted_total_usd " . round($prediction->predictedTotalUsd, 2);

        $lines[] = "# HELP {$prefix}utilization_percent Budget utilization percentage";
        $lines[] = "# TYPE {$prefix}utilization_percent gauge";
        $lines[] = "{$prefix}utilization_percent " . round($prediction->budgetUtilizationPredicted * 100, 1);

        $lines[] = "# HELP {$prefix}on_track Whether spend is on track (1=yes, 0=no)";
        $lines[] = "# TYPE {$prefix}on_track gauge";
        $lines[] = "{$prefix}on_track " . ($prediction->isOnTrack() ? 1 : 0);

        return $lines;
    }

    // ========================================================================
    // ClickHouse Cost
    // ========================================================================

    private function renderClickHouseCostMetrics(): array
    {
        $prefix = self::METRIC_PREFIX . 'clickhouse_';
        $lines = [];

        $today = CarbonImmutable::now();
        $metrics = $this->repository->getClickHouseCostMetrics($today);

        $lines[] = "# HELP {$prefix}storage_cost_usd Estimated ClickHouse storage cost in USD";
        $lines[] = "# TYPE {$prefix}storage_cost_usd gauge";
        $lines[] = "{$prefix}storage_cost_usd " . round((float) ($metrics['estimated_storage_cost_usd'] ?? 0), 4);

        $lines[] = "# HELP {$prefix}compute_cost_usd Estimated ClickHouse compute cost in USD";
        $lines[] = "# TYPE {$prefix}compute_cost_usd gauge";
        $lines[] = "{$prefix}compute_cost_usd " . round((float) ($metrics['estimated_compute_cost_usd'] ?? 0), 4);

        $lines[] = "# HELP {$prefix}total_cost_usd Estimated ClickHouse total cost in USD";
        $lines[] = "# TYPE {$prefix}total_cost_usd gauge";
        $lines[] = "{$prefix}total_cost_usd " . round((float) ($metrics['estimated_total_cost_usd'] ?? 0), 4);

        $lines[] = "# HELP {$prefix}query_count Number of queries executed today";
        $lines[] = "# TYPE {$prefix}query_count gauge";
        $lines[] = "{$prefix}query_count " . ((int) ($metrics['query_count'] ?? 0));

        return $lines;
    }

    // ========================================================================
    // Kafka Cost
    // ========================================================================

    private function renderKafkaCostMetrics(): array
    {
        $prefix = self::METRIC_PREFIX . 'kafka_';
        $lines = [];

        $today = CarbonImmutable::now();
        $metrics = $this->repository->getKafkaCostMetrics($today);

        if (empty($metrics)) {
            return $lines;
        }

        $lines[] = "# HELP {$prefix}total_cost_usd Kafka total cost in USD";
        $lines[] = "# TYPE {$prefix}total_cost_usd gauge";
        $lines[] = "{$prefix}total_cost_usd " . round((float) ($metrics['total_cost_usd'] ?? 0), 4);

        $lines[] = "# HELP {$prefix}bytes_in Total bytes ingested";
        $lines[] = "# TYPE {$prefix}bytes_in gauge";
        $lines[] = "{$prefix}bytes_in " . ((int) ($metrics['bytes_in'] ?? 0));

        return $lines;
    }

    // ========================================================================
    // Unit Economics
    // ========================================================================

    private function renderUnitEconomicsMetrics(): array
    {
        $prefix = self::METRIC_PREFIX . 'unit_';
        $lines = [];

        $endDate = CarbonImmutable::now();
        $startDate = $endDate->subDays(30);
        $unitEcon = $this->repository->getUnitEconomics($startDate, $endDate);

        $lines[] = "# HELP {$prefix}cost_per_1m_events Cost per 1 million events by context";
        $lines[] = "# TYPE {$prefix}cost_per_1m_events gauge";
        foreach ($unitEcon as $row) {
            $context = $row['bounded_context'] ?? 'unknown';
            $lines[] = "{$prefix}cost_per_1m_events{context=\"{$context}\"} " . round((float) ($row['cost_per_1m_events'] ?? 0), 6);
        }

        $lines[] = "# HELP {$prefix}cost_per_query Cost per query by context";
        $lines[] = "# TYPE {$prefix}cost_per_query gauge";
        foreach ($unitEcon as $row) {
            $context = $row['bounded_context'] ?? 'unknown';
            $lines[] = "{$prefix}cost_per_query{context=\"{$context}\"} " . round((float) ($row['cost_per_query'] ?? 0), 6);
        }

        $lines[] = "# HELP {$prefix}cost_to_gmv_ratio Cost to GMV ratio by context";
        $lines[] = "# TYPE {$prefix}cost_to_gmv_ratio gauge";
        foreach ($unitEcon as $row) {
            $context = $row['bounded_context'] ?? 'unknown';
            $lines[] = "{$prefix}cost_to_gmv_ratio{context=\"{$context}\"} " . round((float) ($row['cost_to_gmv_ratio'] ?? 0), 6);
        }

        return $lines;
    }

    // ========================================================================
    // Compression
    // ========================================================================

    private function renderCompressionMetrics(): array
    {
        $prefix = self::METRIC_PREFIX . 'compression_';
        $lines = [];

        $ratios = $this->repository->getCompressionRatios();

        $lines[] = "# HELP {$prefix}ratio Compression ratio per table";
        $lines[] = "# TYPE {$prefix}ratio gauge";
        $count = 0;
        foreach ($ratios as $table => $ratio) {
            if ($count >= 20) {
                break;
            }
            $friendlyName = str_replace('ch_', '', $table);
            $lines[] = "{$prefix}ratio{table=\"{$friendlyName}\"} " . round($ratio, 2);
            $count++;
        }

        return $lines;
    }

    // ========================================================================
    // Storage Growth
    // ========================================================================

    private function renderStorageGrowthMetrics(): array
    {
        $prefix = self::METRIC_PREFIX . 'storage_';
        $lines = [];

        $growthRate = $this->repository->getStorageGrowthRate();

        $lines[] = "# HELP {$prefix}growth_rate_percent Storage growth rate in percent per week";
        $lines[] = "# TYPE {$prefix}growth_rate_percent gauge";
        $lines[] = "{$prefix}growth_rate_percent " . round($growthRate, 2);

        return $lines;
    }

    // ========================================================================
    // Anomalies
    // ========================================================================

    private function renderAnomalyMetrics(): array
    {
        $prefix = self::METRIC_PREFIX . 'anomaly_';
        $lines = [];

        $anomalies = $this->repository->getOpenAnomalies();

        $lines[] = "# HELP {$prefix}open_count Number of open cost anomalies";
        $lines[] = "# TYPE {$prefix}open_count gauge";
        $lines[] = "{$prefix}open_count " . count($anomalies);

        return $lines;
    }
}
