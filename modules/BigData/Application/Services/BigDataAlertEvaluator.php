<?php

declare(strict_types=1);

namespace Modules\BigData\Application\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Modules\BigData\Domain\Enums\AlertSeverity;
use Modules\BigData\Domain\Interfaces\AlertEvaluatorInterface;
use Modules\BigData\Domain\Interfaces\MonitoringRepositoryInterface;
use Modules\BigData\Domain\ValueObjects\AlertResult;
use Modules\BigData\Domain\ValueObjects\MetricThreshold;

/**
 * BigData Alert Evaluator
 *
 * Application-level alert evaluation that complements Prometheus alerting
 * with business-logic-aware checks. Evaluates conditions using the
 * MonitoringRepository and MetricThreshold value objects.
 *
 * Registered alerts:
 * - KafkaConsumerLag: consumer lag > threshold
 * - ClickHouseMergeQueue: merge queue > threshold
 * - ClickHouseDiskUsage: disk usage > threshold %
 * - ClickHouseSlowQueries: P95 latency > threshold
 * - DataFreshnessExpired: table data stale > threshold hours
 * - CLVModelDrift: accuracy drop > threshold
 * - DLQGrowing: DLQ size > threshold
 * - SparkJobDuration: (via Prometheus, not evaluated here)
 * - EventVolumeDrop: (via Prometheus, not evaluated here)
 */
final class BigDataAlertEvaluator implements AlertEvaluatorInterface
{
    /** @var array<string, callable(): AlertResult> */
    private array $evaluators = [];

    /** @var array<string, MetricThreshold> */
    private array $thresholds = [];

    public function __construct(
        private readonly MonitoringRepositoryInterface $repository,
    ) {
        $this->initializeThresholds();
        $this->registerBuiltinAlerts();
    }

    public function evaluateAll(): array
    {
        $results = [];

        foreach ($this->evaluators as $name => $evaluator) {
            try {
                $results[$name] = $evaluator();
            } catch (\Throwable $e) {
                Log::error('BigData alert evaluation failed', [
                    'alert' => $name,
                    'error' => $e->getMessage(),
                ]);

                $results[$name] = AlertResult::firing(
                    name: $name,
                    severity: AlertSeverity::Unknown,
                    message: 'Evaluation failed: ' . $e->getMessage(),
                );
            }
        }

        // Dispatch events for firing alerts
        foreach ($results as $result) {
            if ($result->firing && $result->shouldNotify()) {
                Event::dispatch(new \Modules\BigData\Domain\Events\AlertFired(
                    alertName: $result->name,
                    severity: $result->severity,
                    message: $result->message,
                    details: $result->details,
                ));
            }
        }

        return array_values($results);
    }

    public function evaluateByName(string $alertName): AlertResult
    {
        if (!isset($this->evaluators[$alertName])) {
            return AlertResult::ok($alertName, 'Alert not registered');
        }

        return ($this->evaluators[$alertName])();
    }

    public function getRegisteredAlertNames(): array
    {
        return array_keys($this->evaluators);
    }

    public function registerAlert(string $name, callable $evaluator): void
    {
        $this->evaluators[$name] = $evaluator;
    }

    /**
     * Initialize default thresholds from config and value objects
     */
    private function initializeThresholds(): void
    {
        foreach (MetricThreshold::defaults() as $name => $threshold) {
            $this->thresholds[$name] = $threshold;
        }

        // Override from config if available
        $configOverrides = config('bigdata.monitoring.thresholds', []);
        foreach ($configOverrides as $name => $values) {
            if (isset($values['warning'], $values['critical'])) {
                $this->thresholds[$name] = MetricThreshold::create(
                    metricName: $name,
                    warning: (float) $values['warning'],
                    critical: (float) $values['critical'],
                    unit: $values['unit'] ?? '',
                    forMinutes: $values['for_minutes'] ?? 5,
                );
            }
        }
    }

    /**
     * Register all built-in alert evaluators
     */
    private function registerBuiltinAlerts(): void
    {
        // 1. Kafka Consumer Lag
        $this->evaluators['BigDataKafkaLag'] = function (): AlertResult {
            $metrics = $this->repository->getKafkaConsumerMetrics('bigdata_events');
            $threshold = $this->thresholds['kafka_lag'] ?? MetricThreshold::defaultFor('kafka_lag');
            $pending = $metrics['pending_messages'];

            if ($pending < 0) {
                return AlertResult::ok('BigDataKafkaLag', 'Kafka metrics unavailable');
            }

            $severity = $threshold->evaluate($pending);

            if ($severity !== AlertSeverity::Ok) {
                return AlertResult::firing(
                    name: 'BigDataKafkaLag',
                    severity: $severity,
                    message: "Kafka consumer lag: {$pending} messages pending",
                    details: [
                        'pending_messages' => $pending,
                        'throughput_per_second' => $metrics['throughput_per_second'],
                        'consumers_online' => $metrics['consumers_online'],
                        'threshold_warning' => $threshold->warningThreshold,
                        'threshold_critical' => $threshold->criticalThreshold,
                    ],
                );
            }

            return AlertResult::ok('BigDataKafkaLag', "Kafka lag OK: {$pending} messages pending");
        };

        // 2. ClickHouse Merge Queue
        $this->evaluators['BigDataClickHouseMergeQueue'] = function (): AlertResult {
            $systemMetrics = $this->repository->getClickHouseSystemMetrics();
            $threshold = $this->thresholds['clickhouse_merge_queue'] ?? MetricThreshold::defaultFor('clickhouse_merge_queue');
            $mergeQueue = $systemMetrics['merge_queue_size'];

            if ($mergeQueue < 0) {
                return AlertResult::ok('BigDataClickHouseMergeQueue', 'ClickHouse metrics unavailable');
            }

            $severity = $threshold->evaluate($mergeQueue);

            if ($severity !== AlertSeverity::Ok) {
                return AlertResult::firing(
                    name: 'BigDataClickHouseMergeQueue',
                    severity: $severity,
                    message: "ClickHouse merge queue: {$mergeQueue} merges pending",
                    details: [
                        'merge_queue_size' => $mergeQueue,
                        'mutation_queue_size' => $systemMetrics['mutation_queue_size'],
                        'version' => $systemMetrics['version'],
                    ],
                );
            }

            return AlertResult::ok('BigDataClickHouseMergeQueue', "Merge queue OK: {$mergeQueue} merges");
        };

        // 3. ClickHouse Disk Usage
        $this->evaluators['BigDataClickHouseDiskUsage'] = function (): AlertResult {
            $systemMetrics = $this->repository->getClickHouseSystemMetrics();
            $threshold = $this->thresholds['clickhouse_disk_usage_percent'] ?? MetricThreshold::defaultFor('clickhouse_disk_usage_percent');
            $diskPercent = $systemMetrics['disk_usage_percent'];

            if ($diskPercent < 0) {
                return AlertResult::ok('BigDataClickHouseDiskUsage', 'Disk metrics unavailable');
            }

            $severity = $threshold->evaluate($diskPercent);

            if ($severity !== AlertSeverity::Ok) {
                return AlertResult::firing(
                    name: 'BigDataClickHouseDiskUsage',
                    severity: $severity,
                    message: "ClickHouse disk usage: {$diskPercent}%",
                    details: [
                        'disk_usage_percent' => $diskPercent,
                        'disk_free_bytes' => $systemMetrics['disk_free_bytes'],
                        'threshold_warning' => $threshold->warningThreshold,
                        'threshold_critical' => $threshold->criticalThreshold,
                    ],
                );
            }

            return AlertResult::ok('BigDataClickHouseDiskUsage', "Disk usage OK: {$diskPercent}%");
        };

        // 4. Data Freshness (checks all monitored tables)
        $this->evaluators['BigDataDataFreshness'] = function (): AlertResult {
            $worstSeverity = AlertSeverity::Ok;
            $worstTable = '';
            $worstHours = 0.0;
            $allFreshness = [];

            foreach (\Modules\BigData\Domain\Enums\ClickHouseTableType::monitoredTables() as $tableType) {
                $name = $tableType->friendlyName();
                $lastRecord = $this->repository->getLastRecordTimestamp($name);

                if ($lastRecord === null) {
                    $severity = AlertSeverity::Critical;
                    $hours = -1.0;
                } else {
                    $hours = (float) (CarbonImmutable::now()->diffInSeconds(CarbonImmutable::parse($lastRecord), absolute: true) / 3600);
                    $threshold = $tableType->freshnessThresholdHours();
                    $severity = match (true) {
                        $hours <= $threshold * 0.5 => AlertSeverity::Ok,
                        $hours <= $threshold => AlertSeverity::Warning,
                        default => AlertSeverity::Critical,
                    };
                }

                $allFreshness[$name] = ['hours' => $hours, 'severity' => $severity->value];

                if ($severity->isMoreSevereThan($worstSeverity)) {
                    $worstSeverity = $severity;
                    $worstTable = $name;
                    $worstHours = $hours;
                }
            }

            if ($worstSeverity !== AlertSeverity::Ok) {
                return AlertResult::firing(
                    name: 'BigDataDataFreshness',
                    severity: $worstSeverity,
                    message: "Stalest table: {$worstTable} ({$worstHours}h old)",
                    details: $allFreshness,
                );
            }

            return AlertResult::ok('BigDataDataFreshness', 'All tables fresh');
        };

        // 5. CLV Model Drift
        $this->evaluators['BigDataCLVModelDrift'] = function (): AlertResult {
            $metrics = $this->repository->getCLVModelMetrics();

            if ($metrics['accuracy_current'] < 0) {
                return AlertResult::ok('BigDataCLVModelDrift', 'CLV metrics unavailable');
            }

            $accuracyDrop = max(0, $metrics['accuracy_baseline'] - $metrics['accuracy_current']);
            $threshold = $this->thresholds['clv_accuracy_drop'] ?? MetricThreshold::defaultFor('clv_accuracy_drop');
            $severity = $threshold->evaluate($accuracyDrop);

            if ($severity !== AlertSeverity::Ok) {
                return AlertResult::firing(
                    name: 'BigDataCLVModelDrift',
                    severity: $severity,
                    message: "CLV accuracy drop: " . round($accuracyDrop * 100, 1) . "%",
                    details: [
                        'model_version' => $metrics['model_version'],
                        'accuracy_current' => $metrics['accuracy_current'],
                        'accuracy_baseline' => $metrics['accuracy_baseline'],
                        'accuracy_drop' => $accuracyDrop,
                        'psi_value' => $metrics['psi_value'],
                    ],
                );
            }

            return AlertResult::ok('BigDataCLVModelDrift', "CLV model OK: accuracy={$metrics['accuracy_current']}");
        };

        // 6. DLQ Growing
        $this->evaluators['BigDataDLQGrowing'] = function (): AlertResult {
            $dlqSize = $this->repository->getDLQSize('events');
            $threshold = $this->thresholds['dlq_size'] ?? MetricThreshold::defaultFor('dlq_size');

            if ($dlqSize < 0) {
                return AlertResult::ok('BigDataDLQGrowing', 'DLQ metrics unavailable');
            }

            $severity = $threshold->evaluate($dlqSize);

            if ($severity !== AlertSeverity::Ok) {
                return AlertResult::firing(
                    name: 'BigDataDLQGrowing',
                    severity: $severity,
                    message: "DLQ size: {$dlqSize} messages",
                    details: ['dlq_size' => $dlqSize],
                );
            }

            return AlertResult::ok('BigDataDLQGrowing', "DLQ OK: {$dlqSize} messages");
        };
    }
}
