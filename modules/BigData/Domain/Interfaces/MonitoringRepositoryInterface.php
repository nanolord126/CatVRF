<?php

declare(strict_types=1);

namespace Modules\BigData\Domain\Interfaces;

use Modules\BigData\Domain\Entities\PipelineHealth;
use Modules\BigData\Domain\Entities\DataFreshness;
use Modules\BigData\Domain\Entities\CLVModelDrift;
use Modules\BigData\Domain\Entities\QueryPerformance;

/**
 * Monitoring Repository Interface
 *
 * Contract for reading monitoring metrics from infrastructure sources
 * (Redis, ClickHouse system tables, Kafka consumer groups).
 */
interface MonitoringRepositoryInterface
{
    /**
     * Get Kafka consumer group lag for a topic
     * @return array{pending_messages: int, throughput_per_second: float, consumers_online: int}
     */
    public function getKafkaConsumerMetrics(string $topic): array;

    /**
     * Get the last record timestamp for a ClickHouse table
     * Returns null if table is empty or unreachable
     */
    public function getLastRecordTimestamp(string $tableName): ?string;

    /**
     * Get ClickHouse system metrics
     * @return array{merge_queue_size: int, mutation_queue_size: int, disk_usage_percent: float, disk_free_bytes: int, uptime_seconds: int, version: string}
     */
    public function getClickHouseSystemMetrics(): array;

    /**
     * Get ClickHouse query performance metrics for a named query
     * @return array{p50_ms: float, p95_ms: float, p99_ms: float, qps: int, slow_queries: int, avg_rows: float, avg_bytes: float}
     */
    public function getQueryPerformanceMetrics(string $queryName): array;

    /**
     * Get CLV model metrics from ch_clv_predictions
     * @return array{model_version: string, accuracy_current: float, accuracy_baseline: float, rmse_current: float, rmse_baseline: float, psi_value: float, ks_statistic: float, ks_p_value: float}
     */
    public function getCLVModelMetrics(): array;

    /**
     * Get DLQ (Dead Letter Queue) size
     */
    public function getDLQSize(string $topic): int;

    /**
     * Get event volume for the last hour (per topic)
     */
    public function getEventVolumeLastHour(string $topic): int;

    /**
     * Get table parts count (for merge queue monitoring)
     * @return array<string, int> table_name => parts_count
     */
    public function getTablePartsCount(): array;

    /**
     * Run ClickHouse OPTIMIZE TABLE for a specific table
     */
    public function optimizeTable(string $tableName): bool;

    /**
     * Get feature store record count
     */
    public function getFeatureStoreRecordCount(): int;

    /**
     * Get A/B test active experiments count
     */
    public function getActiveABTestCount(): int;
}
