<?php

declare(strict_types=1);

namespace Modules\BigData\Infrastructure\Repositories;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Modules\BigData\Domain\Interfaces\MonitoringRepositoryInterface;
use Modules\BigData\Infrastructure\ClickHouse\ClickHouseClient;

/**
 * ClickHouse Monitoring Repository
 *
 * Infrastructure implementation of MonitoringRepositoryInterface.
 * Reads metrics from Redis (Kafka stream stats) and ClickHouse system tables.
 *
 * All queries use safe fallbacks — if ClickHouse/Redis is unreachable,
 * returns default/unknown values rather than throwing exceptions.
 */
final class ClickHouseMonitoringRepository implements MonitoringRepositoryInterface
{
    private const KAFKA_STREAM_PREFIX = 'bigdata:events:';
    private const KAFKA_DLQ_PREFIX = 'bigdata:dlq:';
    private const KAFKA_THROUGHPUT_PREFIX = 'bigdata:throughput:';
    private const KAFKA_CONSUMER_PREFIX = 'bigdata:consumers:';

    public function __construct(
        private readonly ClickHouseClient $clickHouse,
    ) {}

    public function getKafkaConsumerMetrics(string $topic): array
    {
        try {
            $streamKey = self::KAFKA_STREAM_PREFIX . $topic;
            $throughputKey = self::KAFKA_THROUGHPUT_PREFIX . $topic;
            $consumerKey = self::KAFKA_CONSUMER_PREFIX . $topic;

            $pendingMessages = (int) Redis::xlen($streamKey);
            $throughput = (float) (Redis::get($throughputKey) ?? 0);
            $consumersOnline = (int) (Redis::get($consumerKey) ?? 0);

            return [
                'pending_messages' => $pendingMessages,
                'throughput_per_second' => $throughput,
                'consumers_online' => $consumersOnline,
            ];
        } catch (\Throwable $e) {
            Log::warning('BigData monitoring: failed to get Kafka metrics', [
                'topic' => $topic,
                'error' => $e->getMessage(),
            ]);

            return [
                'pending_messages' => -1,
                'throughput_per_second' => 0.0,
                'consumers_online' => 0,
            ];
        }
    }

    public function getLastRecordTimestamp(string $tableName): ?string
    {
        try {
            $timestampColumn = match ($tableName) {
                'raw_events' => 'created_at',
                'daily_metrics', 'seller_metrics' => 'metric_date',
                'clv_predictions' => 'prediction_date',
                'abtest_results' => 'metric_date',
                'buyer_seller_features' => 'feature_date',
                'events_hourly' => 'hour',
                default => 'created_at',
            };

            $chTable = match ($tableName) {
                'seller_metrics' => 'ch_seller_daily_metrics',
                default => 'ch_' . $tableName,
            };

            $sql = "SELECT max({$timestampColumn}) AS last_record FROM {$chTable}";
            $result = $this->clickHouse->select($sql);

            if (!empty($result) && isset($result[0]['last_record'])) {
                return (string) $result[0]['last_record'];
            }

            return null;
        } catch (\Throwable $e) {
            Log::warning('BigData monitoring: failed to get last record timestamp', [
                'table' => $tableName,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function getClickHouseSystemMetrics(): array
    {
        $defaults = [
            'merge_queue_size' => -1,
            'mutation_queue_size' => -1,
            'disk_usage_percent' => -1.0,
            'disk_free_bytes' => -1,
            'uptime_seconds' => -1,
            'version' => 'unknown',
        ];

        try {
            $metrics = $defaults;

            // Merge queue
            $mergeResult = $this->clickHouse->select(
                "SELECT count() AS cnt FROM system.merges"
            );
            if (!empty($mergeResult)) {
                $metrics['merge_queue_size'] = (int) ($mergeResult[0]['cnt'] ?? 0);
            }

            // Mutation queue
            $mutationResult = $this->clickHouse->select(
                "SELECT count() AS cnt FROM system.mutations WHERE is_done = 0"
            );
            if (!empty($mutationResult)) {
                $metrics['mutation_queue_size'] = (int) ($mutationResult[0]['cnt'] ?? 0);
            }

            // Disk usage
            $diskResult = $this->clickHouse->select(
                "SELECT
                    formatReadableSize(total_space) AS total,
                    formatReadableSize(used_space) AS used,
                    formatReadableSize(free_space) AS free,
                    round(used_space / total_space * 100, 2) AS usage_percent,
                    free_space AS free_bytes
                FROM system.disks
                LIMIT 1"
            );
            if (!empty($diskResult)) {
                $metrics['disk_usage_percent'] = (float) ($diskResult[0]['usage_percent'] ?? -1);
                $metrics['disk_free_bytes'] = (int) ($diskResult[0]['free_bytes'] ?? -1);
            }

            // Uptime + version
            $infoResult = $this->clickHouse->select(
                "SELECT uptime() AS uptime, version() AS ver"
            );
            if (!empty($infoResult)) {
                $metrics['uptime_seconds'] = (int) ($infoResult[0]['uptime'] ?? -1);
                $metrics['version'] = (string) ($infoResult[0]['ver'] ?? 'unknown');
            }

            return $metrics;
        } catch (\Throwable $e) {
            Log::warning('BigData monitoring: failed to get ClickHouse system metrics', [
                'error' => $e->getMessage(),
            ]);

            return $defaults;
        }
    }

    public function getQueryPerformanceMetrics(string $queryName): array
    {
        $defaults = [
            'p50_ms' => -1.0,
            'p95_ms' => -1.0,
            'p99_ms' => -1.0,
            'qps' => 0,
            'slow_queries' => -1,
            'avg_rows' => -1.0,
            'avg_bytes' => -1.0,
        ];

        try {
            $result = $this->clickHouse->select(
                "SELECT
                    quantile(0.5)(query_duration_ms) AS p50_ms,
                    quantile(0.95)(query_duration_ms) AS p95_ms,
                    quantile(0.99)(query_duration_ms) AS p99_ms,
                    count() AS total_queries,
                    countIf(query_duration_ms > 5000) AS slow_queries,
                    avg(read_rows) AS avg_rows,
                    avg(read_bytes) AS avg_bytes
                FROM system.query_log
                WHERE type = 'QueryFinish'
                  AND event_date >= today() - 1
                  AND query LIKE '%{$queryName}%'
                  AND query NOT LIKE '%system.query_log%'"
            );

            if (!empty($result)) {
                $row = $result[0];
                $totalQueries = (int) ($row['total_queries'] ?? 0);
                // Approximate QPS over last 24h
                $qps = $totalQueries > 0 ? (int) round($totalQueries / 86400) : 0;

                return [
                    'p50_ms' => (float) ($row['p50_ms'] ?? -1),
                    'p95_ms' => (float) ($row['p95_ms'] ?? -1),
                    'p99_ms' => (float) ($row['p99_ms'] ?? -1),
                    'qps' => $qps,
                    'slow_queries' => (int) ($row['slow_queries'] ?? -1),
                    'avg_rows' => (float) ($row['avg_rows'] ?? -1),
                    'avg_bytes' => (float) ($row['avg_bytes'] ?? -1),
                ];
            }

            return $defaults;
        } catch (\Throwable $e) {
            Log::warning('BigData monitoring: failed to get query performance', [
                'query_name' => $queryName,
                'error' => $e->getMessage(),
            ]);

            return $defaults;
        }
    }

    public function getCLVModelMetrics(): array
    {
        $defaults = [
            'model_version' => 'unknown',
            'accuracy_current' => -1.0,
            'accuracy_baseline' => -1.0,
            'rmse_current' => -1.0,
            'rmse_baseline' => -1.0,
            'psi_value' => -1.0,
            'ks_statistic' => -1.0,
            'ks_p_value' => -1.0,
        ];

        try {
            $result = $this->clickHouse->select(
                "SELECT
                    any(model_version) AS model_version,
                    avg(model_confidence) AS accuracy_current,
                    quantile(0.5)(model_confidence) AS median_confidence,
                    count() AS prediction_count,
                    countIf(model_confidence < 0.90) AS low_confidence_count
                FROM ch_clv_predictions
                WHERE prediction_date >= today() - 7"
            );

            if (!empty($result)) {
                $row = $result[0];
                $predictionCount = (int) ($row['prediction_count'] ?? 0);
                $lowConfidence = (int) ($row['low_confidence_count'] ?? 0);

                // Derive accuracy from confidence ratio
                $accuracyCurrent = $predictionCount > 0
                    ? round(1.0 - ($lowConfidence / $predictionCount), 4)
                    : -1.0;

                return [
                    'model_version' => (string) ($row['model_version'] ?? 'unknown'),
                    'accuracy_current' => $accuracyCurrent,
                    'accuracy_baseline' => 0.95, // Configured baseline
                    'rmse_current' => -1.0,
                    'rmse_baseline' => -1.0,
                    'psi_value' => -1.0,
                    'ks_statistic' => -1.0,
                    'ks_p_value' => -1.0,
                ];
            }

            return $defaults;
        } catch (\Throwable $e) {
            Log::warning('BigData monitoring: failed to get CLV model metrics', [
                'error' => $e->getMessage(),
            ]);

            return $defaults;
        }
    }

    public function getDLQSize(string $topic): int
    {
        try {
            return (int) Redis::xlen(self::KAFKA_DLQ_PREFIX . $topic);
        } catch (\Throwable $e) {
            Log::warning('BigData monitoring: failed to get DLQ size', [
                'topic' => $topic,
                'error' => $e->getMessage(),
            ]);

            return -1;
        }
    }

    public function getEventVolumeLastHour(string $topic): int
    {
        try {
            $cacheKey = "bigdata:volume:{$topic}:last_hour";
            return (int) (Redis::get($cacheKey) ?? 0);
        } catch (\Throwable $e) {
            return -1;
        }
    }

    public function getTablePartsCount(): array
    {
        try {
            $result = $this->clickHouse->select(
                "SELECT table, count() AS parts
                FROM system.parts
                WHERE active = 1 AND database = currentDatabase()
                GROUP BY table
                ORDER BY parts DESC"
            );

            $parts = [];
            foreach ($result as $row) {
                $parts[(string) $row['table']] = (int) $row['parts'];
            }

            return $parts;
        } catch (\Throwable $e) {
            Log::warning('BigData monitoring: failed to get table parts count', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    public function optimizeTable(string $tableName): bool
    {
        try {
            $chTable = match ($tableName) {
                'seller_metrics' => 'ch_seller_daily_metrics',
                default => 'ch_' . $tableName,
            };

            $this->clickHouse->execute("OPTIMIZE TABLE {$chTable} FINAL");

            Log::info('BigData monitoring: OPTIMIZE TABLE executed', [
                'table' => $chTable,
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('BigData monitoring: OPTIMIZE TABLE failed', [
                'table' => $tableName,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function getFeatureStoreRecordCount(): int
    {
        try {
            $result = $this->clickHouse->select(
                "SELECT count() AS cnt FROM ch_buyer_seller_features
                WHERE feature_date = today()"
            );

            if (!empty($result)) {
                return (int) ($result[0]['cnt'] ?? 0);
            }

            return 0;
        } catch (\Throwable $e) {
            return -1;
        }
    }

    public function getActiveABTestCount(): int
    {
        try {
            $result = $this->clickHouse->select(
                "SELECT count(DISTINCT test_id) AS cnt FROM ch_abtest_assignments
                WHERE assigned_at >= now() - INTERVAL 30 DAY"
            );

            if (!empty($result)) {
                return (int) ($result[0]['cnt'] ?? 0);
            }

            return 0;
        } catch (\Throwable $e) {
            return -1;
        }
    }
}
