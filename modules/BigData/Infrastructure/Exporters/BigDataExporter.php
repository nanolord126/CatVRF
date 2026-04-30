<?php

declare(strict_types=1);

namespace Modules\BigData\Infrastructure\Exporters;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Modules\BigData\Domain\Enums\ClickHouseTableType;
use Modules\BigData\Domain\Interfaces\MetricsExporterInterface;
use Modules\BigData\Domain\Interfaces\MonitoringRepositoryInterface;

/**
 * BigData Prometheus Exporter
 *
 * Renders custom BigData metrics in Prometheus text exposition format.
 * Implements MetricsExporterInterface from the Domain layer.
 *
 * Metric prefix: catvrf_bigdata_
 * High cardinality protection: no user_id/seller_id in labels,
 * top-N limits on category/segment labels.
 */
final class BigDataExporter implements MetricsExporterInterface
{
    private const METRIC_PREFIX = 'catvrf_bigdata_';

    public function __construct(
        private readonly MonitoringRepositoryInterface $repository,
    ) {}

    public function render(): string
    {
        $lines = [];

        try {
            $lines = array_merge(
                $lines,
                $this->renderPipelineMetrics(),
                $this->renderClickHouseSystemMetrics(),
                $this->renderEventVolumeMetrics(),
                $this->renderFeatureStoreMetrics(),
                $this->renderCLVModelMetrics(),
                $this->renderABTestMetrics(),
                $this->renderDLQMetrics(),
                $this->renderTablePartsMetrics(),
            );
        } catch (\Throwable $e) {
            Log::error('BigData exporter: render failed', ['error' => $e->getMessage()]);

            $lines[] = '# Export failed: ' . $e->getMessage();
        }

        return implode("\n", $lines) . "\n";
    }

    public function getContentType(): string
    {
        return 'text/plain; version=0.0.4; charset=utf-8';
    }

    public function refresh(): void
    {
        // Metrics are fetched on-demand in render() — no cached state to refresh
    }

    // ========================================================================
    // Pipeline Metrics (Kafka consumer lag)
    // ========================================================================

    private function renderPipelineMetrics(): array
    {
        $prefix = self::METRIC_PREFIX . 'pipeline_';
        $lines = [];

        $lines[] = "# HELP {$prefix}kafka_pending_messages Number of pending messages in Kafka stream";
        $lines[] = "# TYPE {$prefix}kafka_pending_messages gauge";

        $metrics = $this->repository->getKafkaConsumerMetrics('bigdata_events');
        $pending = max(0, $metrics['pending_messages']);
        $lines[] = "{$prefix}kafka_pending_messages{topic=\"bigdata_events\"} {$pending}";

        $lines[] = "# HELP {$prefix}kafka_throughput_per_second Current throughput in messages/second";
        $lines[] = "# TYPE {$prefix}kafka_throughput_per_second gauge";
        $lines[] = "{$prefix}kafka_throughput_per_second{topic=\"bigdata_events\"} {$metrics['throughput_per_second']}";

        $lines[] = "# HELP {$prefix}kafka_consumers_online Number of online consumers";
        $lines[] = "# TYPE {$prefix}kafka_consumers_online gauge";
        $lines[] = "{$prefix}kafka_consumers_online{topic=\"bigdata_events\"} {$metrics['consumers_online']}";

        return $lines;
    }

    // ========================================================================
    // ClickHouse System Metrics
    // ========================================================================

    private function renderClickHouseSystemMetrics(): array
    {
        $prefix = self::METRIC_PREFIX . 'clickhouse_';
        $lines = [];

        $system = $this->repository->getClickHouseSystemMetrics();

        $lines[] = "# HELP {$prefix}merge_queue_size Number of pending merges";
        $lines[] = "# TYPE {$prefix}merge_queue_size gauge";
        $lines[] = "{$prefix}merge_queue_size " . max(0, $system['merge_queue_size']);

        $lines[] = "# HELP {$prefix}mutation_queue_size Number of pending mutations";
        $lines[] = "# TYPE {$prefix}mutation_queue_size gauge";
        $lines[] = "{$prefix}mutation_queue_size " . max(0, $system['mutation_queue_size']);

        $lines[] = "# HELP {$prefix}disk_usage_percent Disk usage percentage";
        $lines[] = "# TYPE {$prefix}disk_usage_percent gauge";
        $lines[] = "{$prefix}disk_usage_percent " . max(0, $system['disk_usage_percent']);

        $lines[] = "# HELP {$prefix}disk_free_bytes Free disk space in bytes";
        $lines[] = "# TYPE {$prefix}disk_free_bytes gauge";
        $lines[] = "{$prefix}disk_free_bytes " . max(0, $system['disk_free_bytes']);

        $lines[] = "# HELP {$prefix}uptime_seconds Server uptime in seconds";
        $lines[] = "# TYPE {$prefix}uptime_seconds gauge";
        $lines[] = "{$prefix}uptime_seconds " . max(0, $system['uptime_seconds']);

        $lines[] = "# HELP {$prefix}info ClickHouse version info";
        $lines[] = "# TYPE {$prefix}info gauge";
        $lines[] = "{$prefix}info{version=\"" . $system['version'] . "\"} 1";

        return $lines;
    }

    // ========================================================================
    // Event Volume Metrics
    // ========================================================================

    private function renderEventVolumeMetrics(): array
    {
        $prefix = self::METRIC_PREFIX . 'events_';
        $lines = [];

        $volume = $this->repository->getEventVolumeLastHour('bigdata_events');

        $lines[] = "# HELP {$prefix}volume_last_hour Number of events in the last hour";
        $lines[] = "# TYPE {$prefix}volume_last_hour gauge";
        $lines[] = "{$prefix}volume_last_hour{topic=\"bigdata_events\"} " . max(0, $volume);

        return $lines;
    }

    // ========================================================================
    // Feature Store Metrics
    // ========================================================================

    private function renderFeatureStoreMetrics(): array
    {
        $prefix = self::METRIC_PREFIX . 'feature_store_';
        $lines = [];

        $records = $this->repository->getFeatureStoreRecordCount();

        $lines[] = "# HELP {$prefix}records_total Number of feature store records for today";
        $lines[] = "# TYPE {$prefix}records_total gauge";
        $lines[] = "{$prefix}records_total " . max(0, $records);

        return $lines;
    }

    // ========================================================================
    // CLV Model Metrics
    // ========================================================================

    private function renderCLVModelMetrics(): array
    {
        $prefix = self::METRIC_PREFIX . 'clv_model_';
        $lines = [];

        $metrics = $this->repository->getCLVModelMetrics();

        $lines[] = "# HELP {$prefix}accuracy_current Current model accuracy (confidence ratio)";
        $lines[] = "# TYPE {$prefix}accuracy_current gauge";
        $lines[] = "{$prefix}accuracy_current{version=\"" . $metrics['model_version'] . "\"} " . max(0, $metrics['accuracy_current']);

        $lines[] = "# HELP {$prefix}accuracy_baseline Baseline model accuracy";
        $lines[] = "# TYPE {$prefix}accuracy_baseline gauge";
        $lines[] = "{$prefix}accuracy_baseline " . max(0, $metrics['accuracy_baseline']);

        $lines[] = "# HELP {$prefix}accuracy_drop Accuracy drop from baseline";
        $lines[] = "# TYPE {$prefix}accuracy_drop gauge";
        $drop = max(0, $metrics['accuracy_baseline'] - $metrics['accuracy_current']);
        $lines[] = "{$prefix}accuracy_drop " . round($drop, 4);

        if ($metrics['psi_value'] >= 0) {
            $lines[] = "# HELP {$prefix}psi Population Stability Index";
            $lines[] = "# TYPE {$prefix}psi gauge";
            $lines[] = "{$prefix}psi " . round($metrics['psi_value'], 4);
        }

        return $lines;
    }

    // ========================================================================
    // A/B Test Metrics
    // ========================================================================

    private function renderABTestMetrics(): array
    {
        $prefix = self::METRIC_PREFIX . 'abtest_';
        $lines = [];

        $activeTests = $this->repository->getActiveABTestCount();

        $lines[] = "# HELP {$prefix}active_count Number of active A/B tests";
        $lines[] = "# TYPE {$prefix}active_count gauge";
        $lines[] = "{$prefix}active_count " . max(0, $activeTests);

        return $lines;
    }

    // ========================================================================
    // DLQ Metrics
    // ========================================================================

    private function renderDLQMetrics(): array
    {
        $prefix = self::METRIC_PREFIX . 'dlq_';
        $lines = [];

        $dlqSize = $this->repository->getDLQSize('events');

        $lines[] = "# HELP {$prefix}size Number of messages in the Dead Letter Queue";
        $lines[] = "# TYPE {$prefix}size gauge";
        $lines[] = "{$prefix}size{topic=\"events\"} " . max(0, $dlqSize);

        return $lines;
    }

    // ========================================================================
    // Table Parts Metrics
    // ========================================================================

    private function renderTablePartsMetrics(): array
    {
        $prefix = self::METRIC_PREFIX . 'clickhouse_table_parts';
        $lines = [];

        $lines[] = "# HELP {$prefix} Number of active parts per ClickHouse table";
        $lines[] = "# TYPE {$prefix} gauge";

        $parts = $this->repository->getTablePartsCount();
        $count = 0;

        foreach ($parts as $table => $partCount) {
            if ($count >= 20) {
                break; // High cardinality protection: top 20 tables
            }

            $friendlyName = str_replace('ch_', '', $table);
            $lines[] = "{$prefix}{table=\"{$friendlyName}\"} {$partCount}";
            $count++;
        }

        return $lines;
    }
}
