<?php

declare(strict_types=1);

namespace Modules\BigData\Application\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Modules\BigData\Domain\Entities\CLVModelDrift;
use Modules\BigData\Domain\Entities\DataFreshness;
use Modules\BigData\Domain\Entities\PipelineHealth;
use Modules\BigData\Domain\Entities\QueryPerformance;
use Modules\BigData\Domain\Enums\ClickHouseTableType;
use Modules\BigData\Domain\Enums\HealthStatus;
use Modules\BigData\Domain\Events\AlertFired;
use Modules\BigData\Domain\Events\CLVDriftDetected;
use Modules\BigData\Domain\Events\PipelineStatusChanged;
use Modules\BigData\Domain\Interfaces\AlertEvaluatorInterface;
use Modules\BigData\Domain\Interfaces\MonitoringRepositoryInterface;
use Modules\BigData\Domain\ValueObjects\AlertResult;
use Modules\BigData\Domain\ValueObjects\MetricThreshold;

/**
 * BigData Monitoring Facade
 *
 * Application service providing the monitoring API for the BigData vertical.
 * Orchestrates domain entities, repository reads, alert evaluation, and self-healing.
 *
 * Usage:
 *   BigData::monitor()->getPipelineHealth()
 *   BigData::monitor()->getDataFreshness('seller_metrics')
 *   BigData::monitor()->getCLVModelDrift()
 *   BigData::monitor()->getQueryPerformance('top_products')
 *   BigData::monitor()->alertIfLagOver('kafka.raw_events', 300)
 */
final class BigDataMonitoringFacade
{
    private ?PipelineHealth $lastPipelineHealth = null;

    public function __construct(
        private readonly MonitoringRepositoryInterface $repository,
        private readonly AlertEvaluatorInterface $alertEvaluator,
    ) {}

    // ========================================================================
    // Pipeline Health
    // ========================================================================

    /**
     * Get Kafka → ClickHouse pipeline health
     */
    public function getPipelineHealth(string $topic = 'bigdata_events'): PipelineHealth
    {
        $metrics = $this->repository->getKafkaConsumerMetrics($topic);

        if ($metrics['pending_messages'] < 0) {
            $health = PipelineHealth::unknown(
                topic: $topic,
                consumersExpected: config('bigdata.monitoring.consumer_count', 1),
            );
        } else {
            $health = PipelineHealth::fromMetrics(
                topic: $topic,
                pendingMessages: $metrics['pending_messages'],
                throughputPerSecond: $metrics['throughput_per_second'],
                consumersOnline: $metrics['consumers_online'],
                consumersExpected: config('bigdata.monitoring.consumer_count', 1),
                degradedThresholdSeconds: config('bigdata.monitoring.degraded_lag_seconds', 60.0),
                criticalThresholdSeconds: config('bigdata.monitoring.critical_lag_seconds', 300.0),
            );
        }

        // Dispatch domain event if status changed
        if ($this->lastPipelineHealth !== null && $this->lastPipelineHealth->status !== $health->status) {
            Event::dispatch(new PipelineStatusChanged(
                topic: $topic,
                previousStatus: $this->lastPipelineHealth->status,
                currentStatus: $health->status,
                lagSeconds: $health->lagSeconds,
                throughputPerSecond: $health->throughputPerSecond,
            ));
        }

        $this->lastPipelineHealth = $health;

        return $health;
    }

    // ========================================================================
    // Data Freshness
    // ========================================================================

    /**
     * Get data freshness for a specific table
     */
    public function getDataFreshness(string $tableName): DataFreshness
    {
        $lastRecord = $this->repository->getLastRecordTimestamp($tableName);

        if ($lastRecord === null) {
            return DataFreshness::noData($tableName);
        }

        return DataFreshness::fromLastRecord($tableName, $lastRecord);
    }

    /**
     * Get data freshness for all monitored tables
     * @return array<string, DataFreshness>
     */
    public function getAllDataFreshness(): array
    {
        $results = [];
        foreach (ClickHouseTableType::monitoredTables() as $tableType) {
            $name = $tableType->friendlyName();
            $results[$name] = $this->getDataFreshness($name);
        }

        return $results;
    }

    // ========================================================================
    // CLV Model Drift
    // ========================================================================

    /**
     * Get CLV model drift metrics
     */
    public function getCLVModelDrift(): CLVModelDrift
    {
        $metrics = $this->repository->getCLVModelMetrics();

        if ($metrics['accuracy_current'] < 0) {
            return CLVModelDrift::unknown();
        }

        $drift = CLVModelDrift::fromMetrics(
            modelVersion: $metrics['model_version'],
            accuracyCurrent: $metrics['accuracy_current'],
            accuracyBaseline: $metrics['accuracy_baseline'],
            rmseCurrent: $metrics['rmse_current'],
            rmseBaseline: $metrics['rmse_baseline'],
            psiValue: $metrics['psi_value'],
            ksStatistic: $metrics['ks_statistic'],
            ksPValue: $metrics['ks_p_value'],
        );

        // Dispatch domain event if drift requires attention
        if ($drift->hasDrift() && $drift->requiresScheduledRetraining()) {
            Event::dispatch(new CLVDriftDetected(
                modelVersion: $drift->modelVersion,
                driftStatus: $drift->driftStatus,
                accuracyDrop: $drift->accuracyDrop,
                psiValue: $drift->psiValue,
                requiresRetraining: $drift->requiresImmediateRetraining(),
            ));
        }

        return $drift;
    }

    // ========================================================================
    // Query Performance
    // ========================================================================

    /**
     * Get query performance for a named query
     */
    public function getQueryPerformance(string $queryName): QueryPerformance
    {
        $metrics = $this->repository->getQueryPerformanceMetrics($queryName);

        if ($metrics['p95_ms'] < 0) {
            return QueryPerformance::unknown($queryName);
        }

        return QueryPerformance::fromQueryLogMetrics(
            queryName: $queryName,
            p50Ms: $metrics['p50_ms'],
            p95Ms: $metrics['p95_ms'],
            p99Ms: $metrics['p99_ms'],
            qps: $metrics['qps'],
            slowQueries: $metrics['slow_queries'],
            avgRows: $metrics['avg_rows'],
            avgBytes: $metrics['avg_bytes'],
        );
    }

    // ========================================================================
    // Alerting
    // ========================================================================

    /**
     * Evaluate alert: is lag over the given threshold?
     */
    public function alertIfLagOver(string $topic, float $thresholdSeconds): AlertResult
    {
        $health = $this->getPipelineHealth($topic);

        if ($health->lagSeconds >= $thresholdSeconds) {
            $result = AlertResult::firing(
                name: "BigDataKafkaLag.{$topic}",
                severity: $health->status === HealthStatus::Critical
                    ? \Modules\BigData\Domain\Enums\AlertSeverity::Critical
                    : \Modules\BigData\Domain\Enums\AlertSeverity::Warning,
                message: "Kafka lag for {$topic} is {$health->lagSeconds}s (threshold: {$thresholdSeconds}s)",
                details: $health->toArray(),
            );

            Event::dispatch(AlertFired::fromAlertResult($result));

            return $result;
        }

        return AlertResult::ok(
            name: "BigDataKafkaLag.{$topic}",
            message: "Kafka lag for {$topic} is {$health->lagSeconds}s (under threshold: {$thresholdSeconds}s)",
        );
    }

    /**
     * Evaluate all registered alerts
     * @return array<AlertResult>
     */
    public function evaluateAlerts(): array
    {
        return $this->alertEvaluator->evaluateAll();
    }

    // ========================================================================
    // Self-Healing
    // ========================================================================

    /**
     * Restart Kafka consumers if pipeline is critical
     * @return array{restarted: bool, reason: string, topic: string}
     */
    public function restartConsumers(string $topic = 'bigdata_events'): array
    {
        $health = $this->getPipelineHealth($topic);

        if (!$health->needsSelfHealing()) {
            return [
                'restarted' => false,
                'reason' => 'Self-healing not needed: pipeline is ' . $health->status->value,
                'topic' => $topic,
            ];
        }

        try {
            // Terminate Horizon workers — they will auto-restart
            Artisan::call('horizon:terminate');

            Log::warning('BigData monitoring: self-healing triggered — restarting consumers', [
                'topic' => $topic,
                'lag_seconds' => $health->lagSeconds,
                'consumer_deficit' => $health->consumerDeficit(),
            ]);

            return [
                'restarted' => true,
                'reason' => "Pipeline critical: lag={$health->lagSeconds}s, deficit={$health->consumerDeficit()}",
                'topic' => $topic,
            ];
        } catch (\Throwable $e) {
            Log::error('BigData monitoring: self-healing failed', [
                'topic' => $topic,
                'error' => $e->getMessage(),
            ]);

            return [
                'restarted' => false,
                'reason' => 'Self-healing failed: ' . $e->getMessage(),
                'topic' => $topic,
            ];
        }
    }

    // ========================================================================
    // Maintenance
    // ========================================================================

    /**
     * Run ClickHouse maintenance: OPTIMIZE tables with too many parts
     * @return array<string, bool> table_name => success
     */
    public function runMaintenance(): array
    {
        $results = [];
        $partsCount = $this->repository->getTablePartsCount();

        foreach ($partsCount as $table => $parts) {
            // Only OPTIMIZE tables with > 100 active parts
            if ($parts > 100) {
                $friendlyName = str_replace('ch_', '', $table);
                $results[$friendlyName] = $this->repository->optimizeTable($friendlyName);
            }
        }

        Log::info('BigData monitoring: maintenance completed', [
            'tables_optimized' => count($results),
            'results' => $results,
        ]);

        return $results;
    }

    // ========================================================================
    // Snapshot
    // ========================================================================

    /**
     * Get a comprehensive monitoring snapshot (for API endpoint)
     */
    public function getSnapshot(): array
    {
        return [
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
            'pipeline' => $this->getPipelineHealth()->toArray(),
            'freshness' => array_map(
                fn(DataFreshness $f) => $f->toArray(),
                $this->getAllDataFreshness(),
            ),
            'clv_drift' => $this->getCLVModelDrift()->toArray(),
            'query_performance' => [
                'seller_dashboard' => $this->getQueryPerformance('seller_dashboard')->toArray(),
                'top_products' => $this->getQueryPerformance('top_products')->toArray(),
            ],
            'clickhouse_system' => $this->repository->getClickHouseSystemMetrics(),
            'dlq_size' => $this->repository->getDLQSize('events'),
            'feature_store_records' => $this->repository->getFeatureStoreRecordCount(),
            'active_abtests' => $this->repository->getActiveABTestCount(),
            'alerts' => array_map(
                fn(AlertResult $a) => $a->toArray(),
                $this->evaluateAlerts(),
            ),
        ];
    }
}
