<?php

declare(strict_types=1);

namespace Modules\BigData\Domain\ValueObjects;

use Carbon\CarbonImmutable;
use Modules\BigData\Domain\Enums\AlertSeverity;

/**
 * Metric Threshold Value Object
 *
 * Immutable configuration for alert thresholds.
 * Encapsulates threshold values with validation and comparison logic.
 */
final class MetricThreshold
{
    private function __construct(
        public readonly string $metricName,
        public readonly float $warningThreshold,
        public readonly float $criticalThreshold,
        public readonly string $unit,
        public readonly int $forMinutes,
        public readonly ?CarbonImmutable $updatedAt = null,
    ) {
        $this->validate();
    }

    /**
     * Factory: create a new threshold
     */
    public static function create(
        string $metricName,
        float $warning,
        float $critical,
        string $unit = '',
        int $forMinutes = 5,
    ): self {
        return new self(
            metricName: $metricName,
            warningThreshold: $warning,
            criticalThreshold: $critical,
            unit: $unit,
            forMinutes: $forMinutes,
            updatedAt: CarbonImmutable::now(),
        );
    }

    /**
     * Factory: from stored config
     */
    public static function fromArray(array $data): self
    {
        return new self(
            metricName: $data['metric_name'],
            warningThreshold: $data['warning_threshold'],
            criticalThreshold: $data['critical_threshold'],
            unit: $data['unit'] ?? '',
            forMinutes: $data['for_minutes'] ?? 5,
            updatedAt: isset($data['updated_at']) ? CarbonImmutable::parse($data['updated_at']) : null,
        );
    }

    /**
     * Factory: default thresholds for BigData monitoring
     * @return array<string, self>
     */
    public static function defaults(): array
    {
        return [
            'kafka_lag' => self::create('kafka_lag', 5000, 10000, 'messages', 5),
            'kafka_lag_seconds' => self::create('kafka_lag_seconds', 60, 300, 'seconds', 5),
            'clickhouse_merge_queue' => self::create('clickhouse_merge_queue', 500, 1000, 'parts', 10),
            'clickhouse_disk_usage_percent' => self::create('clickhouse_disk_usage_percent', 75, 85, '%', 5),
            'clickhouse_query_p95_ms' => self::create('clickhouse_query_p95_ms', 2000, 5000, 'ms', 5),
            'clv_accuracy_drop' => self::create('clv_accuracy_drop', 0.05, 0.08, 'ratio', 15),
            'clv_psi' => self::create('clv_psi', 0.2, 0.5, 'index', 15),
            'data_freshness_hours' => self::create('data_freshness_hours', 2, 4, 'hours', 5),
            'dlq_size' => self::create('dlq_size', 50, 100, 'messages', 5),
            'spark_job_duration_multiplier' => self::create('spark_job_duration_multiplier', 1.5, 2.0, 'x', 10),
            'event_volume_drop_percent' => self::create('event_volume_drop_percent', 30, 50, '%', 10),
        ];
    }

    /**
     * Get a specific default threshold by metric name
     */
    public static function defaultFor(string $metricName): ?self
    {
        return self::defaults()[$metricName] ?? null;
    }

    /**
     * Business rule: evaluate a value against this threshold
     */
    public function evaluate(float $value): AlertSeverity
    {
        if ($value < 0) {
            return AlertSeverity::Unknown;
        }

        return match (true) {
            $value >= $this->criticalThreshold => AlertSeverity::Critical,
            $value >= $this->warningThreshold => AlertSeverity::Warning,
            default => AlertSeverity::Ok,
        };
    }

    /**
     * Business rule: is a value within acceptable range?
     */
    public function isWithinRange(float $value): bool
    {
        return $value >= 0 && $value < $this->warningThreshold;
    }

    /**
     * Business rule: how close is the value to the warning threshold? (0-100%)
     */
    public function proximityToWarning(float $value): float
    {
        if ($this->warningThreshold <= 0) {
            return 100.0;
        }

        return min(100.0, round(($value / $this->warningThreshold) * 100, 1));
    }

    /**
     * Business rule: create a modified threshold with new values
     */
    public function withThresholds(float $warning, float $critical): self
    {
        return new self(
            metricName: $this->metricName,
            warningThreshold: $warning,
            criticalThreshold: $critical,
            unit: $this->unit,
            forMinutes: $this->forMinutes,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function toArray(): array
    {
        return [
            'metric_name' => $this->metricName,
            'warning_threshold' => $this->warningThreshold,
            'critical_threshold' => $this->criticalThreshold,
            'unit' => $this->unit,
            'for_minutes' => $this->forMinutes,
            'updated_at' => $this->updatedAt?->toIso8601String(),
        ];
    }

    private function validate(): void
    {
        if (trim($this->metricName) === '') {
            throw new \InvalidArgumentException('MetricThreshold metricName cannot be empty');
        }

        if ($this->warningThreshold < 0 || $this->criticalThreshold < 0) {
            throw new \InvalidArgumentException('MetricThreshold values must be >= 0');
        }

        if ($this->warningThreshold > $this->criticalThreshold) {
            throw new \InvalidArgumentException(
                "MetricThreshold warning ({$this->warningThreshold}) must be <= critical ({$this->criticalThreshold})"
            );
        }

        if ($this->forMinutes < 1) {
            throw new \InvalidArgumentException('MetricThreshold forMinutes must be >= 1');
        }
    }
}
