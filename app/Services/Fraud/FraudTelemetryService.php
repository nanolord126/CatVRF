<?php declare(strict_types=1);

namespace App\Services\Fraud;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Log\LogManager;
use Illuminate\Support\Facades\Redis;

/**
 * Fraud Telemetry Service for Prometheus Metrics
 * 
 * Tracks fraud detection metrics:
 * - Total operations checked
 * - Blocked/Review/Allow decisions
 * - ML inference latency
 * - False positive/negative rates
 * - Circuit breaker status
 */
final readonly class FraudTelemetryService
{
    private const METRICS_PREFIX = 'fraud_';
    private const METRICS_TTL = 86400; // 24 hours

    public function __construct(
        private readonly RedisFactory $redis,
        private readonly Repository $cache,
        private readonly LogManager $logger,
    ) {}

    /**
     * Record fraud check operation
     */
    public function recordCheck(
        string $operationType,
        float $score,
        string $decision,
        float $latencyMs,
        ?string $correlationId = null,
    ): void {
        $timestamp = now()->timestamp;
        $hourBucket = (int) floor($timestamp / 3600) * 3600;

        $pipe = Redis::pipeline();

        // Total checks counter
        $pipe->incr(self::METRICS_PREFIX . 'checks_total');
        $pipe->incr(self::METRICS_PREFIX . "checks_operation_{$operationType}");
        $pipe->incr(self::METRICS_PREFIX . "checks_decision_{$decision}");

        // Score histogram buckets
        $this->incrementScoreBucket($pipe, $score);

        // Latency tracking
        $pipe->incrby(self::METRICS_PREFIX . 'latency_ms_total', (int) $latencyMs);
        $pipe->incr(self::METRICS_PREFIX . 'latency_count');

        // Hourly time series for trends
        $hourlyKey = self::METRICS_PREFIX . "checks_hourly:{$hourBucket}";
        $pipe->hincrby($hourlyKey, $decision, 1);
        $pipe->expire($hourlyKey, self::METRICS_TTL);

        // Execute pipeline
        $pipe->exec();

        // Log high scores for alerting
        if ($score > 0.8) {
            $this->logger->channel('fraud_alert')->warning('High fraud score detected', [
                'correlation_id' => $correlationId,
                'operation_type' => $operationType,
                'score' => $score,
                'decision' => $decision,
            ]);
        }
    }

    /**
     * Increment appropriate score bucket for histogram
     */
    private function incrementScoreBucket($pipe, float $score): void
    {
        $buckets = [0.1, 0.2, 0.3, 0.4, 0.5, 0.6, 0.7, 0.8, 0.9, 1.0];
        
        foreach ($buckets as $bucket) {
            if ($score <= $bucket) {
                $pipe->incr(self::METRICS_PREFIX . "score_bucket_le_{$bucket}");
            }
        }
    }

    /**
     * Record ML inference metrics
     */
    public function recordMLInference(
        bool $success,
        float $latencyMs,
        bool $circuitOpen,
        ?string $modelVersion = null,
    ): void {
        $pipe = Redis::pipeline();

        $pipe->incr(self::METRICS_PREFIX . 'ml_inference_total');
        
        if ($success) {
            $pipe->incr(self::METRICS_PREFIX . 'ml_inference_success');
        } else {
            $pipe->incr(self::METRICS_PREFIX . 'ml_inference_failure');
        }

        $pipe->incrby(self::METRICS_PREFIX . 'ml_latency_ms_total', (int) $latencyMs);
        $pipe->incr(self::METRICS_PREFIX . 'ml_latency_count');

        if ($circuitOpen) {
            $pipe->incr(self::METRICS_PREFIX . 'ml_circuit_open');
        }

        if ($modelVersion) {
            $pipe->hincrby(self::METRICS_PREFIX . 'ml_model_version', $modelVersion, 1);
        }

        $pipe->exec();
    }

    /**
     * Record atomic lock operations
     */
    public function recordAtomicLock(
        string $operation, // 'slot_hold' or 'payment'
        bool $success,
        string $reason,
    ): void {
        Redis::pipeline()
            ->incr(self::METRICS_PREFIX . "atomic_lock_{$operation}_total")
            ->incr($success 
                ? self::METRICS_PREFIX . "atomic_lock_{$operation}_success"
                : self::METRICS_PREFIX . "atomic_lock_{$operation}_failure")
            ->incr(self::METRICS_PREFIX . "atomic_lock_reason_{$reason}")
            ->exec();
    }

    /**
     * Get Prometheus metrics in text format
     */
    public function getPrometheusMetrics(): string
    {
        $lines = [];

        // Counter metrics
        $lines[] = $this->formatCounter(
            'fraud_checks_total',
            $this->getCounter('checks_total'),
            'Total number of fraud checks performed'
        );

        $lines[] = $this->formatCounter(
            'fraud_checks_decision_allow',
            $this->getCounter('checks_decision_allow'),
            'Number of fraud checks with allow decision'
        );

        $lines[] = $this->formatCounter(
            'fraud_checks_decision_review',
            $this->getCounter('checks_decision_review'),
            'Number of fraud checks with review decision'
        );

        $lines[] = $this->formatCounter(
            'fraud_checks_decision_block',
            $this->getCounter('checks_decision_block'),
            'Number of fraud checks with block decision'
        );

        // ML metrics
        $lines[] = $this->formatCounter(
            'fraud_ml_inference_total',
            $this->getCounter('ml_inference_total'),
            'Total ML inference calls'
        );

        $lines[] = $this->formatCounter(
            'fraud_ml_inference_success',
            $this->getCounter('ml_inference_success'),
            'Successful ML inference calls'
        );

        $lines[] = $this->formatCounter(
            'fraud_ml_inference_failure',
            $this->getCounter('ml_inference_failure'),
            'Failed ML inference calls'
        );

        $lines[] = $this->formatGauge(
            'fraud_ml_circuit_open',
            $this->getCircuitBreakerStatus() ? 1 : 0,
            'ML circuit breaker status (1 = open, 0 = closed)'
        );

        // Latency metrics (averages)
        $avgLatency = $this->getAverageLatency('latency');
        $lines[] = $this->formatGauge(
            'fraud_check_latency_ms_avg',
            $avgLatency,
            'Average fraud check latency in milliseconds'
        );

        $avgMLLatency = $this->getAverageLatency('ml_latency');
        $lines[] = $this->formatGauge(
            'fraud_ml_inference_latency_ms_avg',
            $avgMLLatency,
            'Average ML inference latency in milliseconds'
        );

        // Block rate
        $totalChecks = $this->getCounter('checks_total');
        $blockedChecks = $this->getCounter('checks_decision_block');
        $blockRate = $totalChecks > 0 ? ($blockedChecks / $totalChecks) * 100 : 0;
        $lines[] = $this->formatGauge(
            'fraud_block_rate_percent',
            round($blockRate, 2),
            'Percentage of operations blocked by fraud detection'
        );

        // Score histogram buckets
        $buckets = [0.1, 0.2, 0.3, 0.4, 0.5, 0.6, 0.7, 0.8, 0.9, 1.0];
        foreach ($buckets as $bucket) {
            $lines[] = $this->formatHistogram(
                'fraud_score_bucket',
                $this->getCounter("score_bucket_le_{$bucket}"),
                $bucket,
                'Fraud score histogram'
            );
        }

        return implode("\n", $lines) . "\n";
    }

    /**
     * Format Prometheus counter metric
     */
    private function formatCounter(string $name, float $value, string $help): string
    {
        return "# HELP {$name} {$help}\n# TYPE {$name} counter\n{$name} {$value}";
    }

    /**
     * Format Prometheus gauge metric
     */
    private function formatGauge(string $name, float $value, string $help): string
    {
        return "# HELP {$name} {$help}\n# TYPE {$name} gauge\n{$name} {$value}";
    }

    /**
     * Format Prometheus histogram bucket
     */
    private function formatHistogram(string $name, float $value, float $le, string $help): string
    {
        return "# HELP {$name} {$help}\n# TYPE {$name} histogram\n{$name}_bucket{{$this->formatLe($le)}} {$value}";
    }

    /**
     * Format label for histogram bucket
     */
    private function formatLe(float $le): string
    {
        return "le=\"{$le}\"";
    }

    /**
     * Get counter value from Redis
     */
    private function getCounter(string $suffix): float
    {
        return (float) Redis::get(self::METRICS_PREFIX . $suffix) ?: 0;
    }

    /**
     * Get average latency
     */
    private function getAverageLatency(string $type): float
    {
        $total = (float) Redis::get(self::METRICS_PREFIX . "{$type}_ms_total") ?: 0;
        $count = (float) Redis::get(self::METRICS_PREFIX . "{$type}_count") ?: 0;

        return $count > 0 ? $total / $count : 0;
    }

    /**
     * Get circuit breaker status
     */
    private function getCircuitBreakerStatus(): bool
    {
        return (bool) Redis::get('fraud:ml:circuit_breaker');
    }

    /**
     * Get fraud statistics for dashboard
     */
    public function getStatistics(int $hours = 24): array
    {
        $now = now();
        $stats = [
            'total_checks' => $this->getCounter('checks_total'),
            'blocked' => $this->getCounter('checks_decision_block'),
            'reviewed' => $this->getCounter('checks_decision_review'),
            'allowed' => $this->getCounter('checks_decision_allow'),
            'block_rate' => 0,
            'avg_latency_ms' => $this->getAverageLatency('latency'),
            'ml_success_rate' => 0,
            'ml_avg_latency_ms' => $this->getAverageLatency('ml_latency'),
            'circuit_breaker_open' => $this->getCircuitBreakerStatus(),
            'hourly_trends' => [],
        ];

        $totalChecks = $stats['total_checks'];
        $stats['block_rate'] = $totalChecks > 0 ? ($stats['blocked'] / $totalChecks) * 100 : 0;

        $mlTotal = $this->getCounter('ml_inference_total');
        $mlSuccess = $this->getCounter('ml_inference_success');
        $stats['ml_success_rate'] = $mlTotal > 0 ? ($mlSuccess / $mlTotal) * 100 : 0;

        // Hourly trends
        for ($i = 0; $i < $hours; $i++) {
            $timestamp = $now->copy()->subHours($i)->timestamp;
            $hourBucket = (int) floor($timestamp / 3600) * 3600;
            $hourlyKey = self::METRICS_PREFIX . "checks_hourly:{$hourBucket}";
            $hourlyData = Redis::hgetall($hourlyKey);

            $stats['hourly_trends'][] = [
                'hour' => $now->copy()->subHours($i)->format('Y-m-d H:00'),
                'allow' => (int) ($hourlyData['allow'] ?? 0),
                'review' => (int) ($hourlyData['review'] ?? 0),
                'block' => (int) ($hourlyData['block'] ?? 0),
            ];
        }

        return $stats;
    }

    /**
     * Reset metrics (for testing or manual intervention)
     */
    public function resetMetrics(): void
    {
        $pattern = self::METRICS_PREFIX . '*';
        $keys = Redis::keys($pattern);

        if (!empty($keys)) {
            Redis::del($keys);
        }

        $this->logger->channel('fraud_alert')->info('Fraud metrics reset');
    }
}
