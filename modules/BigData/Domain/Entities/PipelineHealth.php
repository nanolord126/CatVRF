<?php

declare(strict_types=1);

namespace Modules\BigData\Domain\Entities;

use Carbon\CarbonImmutable;
use Modules\BigData\Domain\Enums\HealthStatus;

/**
 * Pipeline Health Entity
 *
 * Represents the health of the Kafka → ClickHouse data pipeline.
 * Enforces domain invariants:
 * - Lag seconds must be non-negative (or -1 for unknown)
 * - Status is derived from lag, not set arbitrarily
 * - Throughput must be >= 0
 * - Consumer counts must be >= 0
 */
final class PipelineHealth
{
    private function __construct(
        public readonly string $topic,
        public readonly int $consumerLag,
        public readonly float $throughputPerSecond,
        public readonly int $pendingMessages,
        public readonly int $consumersOnline,
        public readonly int $consumersExpected,
        public readonly float $lagSeconds,
        public readonly HealthStatus $status,
        public readonly CarbonImmutable $calculatedAt,
    ) {
        $this->validate();
    }

    /**
     * Factory: create from raw Redis/Kafka metrics
     */
    public static function fromMetrics(
        string $topic,
        int $pendingMessages,
        float $throughputPerSecond,
        int $consumersOnline,
        int $consumersExpected,
        float $degradedThresholdSeconds = 60.0,
        float $criticalThresholdSeconds = 300.0,
    ): self {
        // Calculate lag: if throughput is 0 and there are pending messages, lag is infinite
        $lagSeconds = match (true) {
            $pendingMessages < 0 => -1.0,
            $throughputPerSecond <= 0 && $pendingMessages > 0 => 999999.0,
            $throughputPerSecond <= 0 => 0.0,
            default => round($pendingMessages / $throughputPerSecond, 2),
        };

        $status = HealthStatus::fromLagSeconds($lagSeconds, $degradedThresholdSeconds, $criticalThresholdSeconds);

        return new self(
            topic: $topic,
            consumerLag: $pendingMessages,
            throughputPerSecond: $throughputPerSecond,
            pendingMessages: $pendingMessages,
            consumersOnline: $consumersOnline,
            consumersExpected: $consumersExpected,
            lagSeconds: $lagSeconds,
            status: $status,
            calculatedAt: CarbonImmutable::now(),
        );
    }

    /**
     * Factory: create when metrics source is unavailable
     */
    public static function unknown(string $topic, int $consumersExpected = 1): self
    {
        return new self(
            topic: $topic,
            consumerLag: -1,
            throughputPerSecond: 0.0,
            pendingMessages: -1,
            consumersOnline: 0,
            consumersExpected: $consumersExpected,
            lagSeconds: -1.0,
            status: HealthStatus::Unknown,
            calculatedAt: CarbonImmutable::now(),
        );
    }

    /**
     * Factory: create from stored snapshot data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            topic: $data['topic'],
            consumerLag: $data['consumer_lag'],
            throughputPerSecond: $data['throughput_per_second'],
            pendingMessages: $data['pending_messages'],
            consumersOnline: $data['consumers_online'],
            consumersExpected: $data['consumers_expected'],
            lagSeconds: $data['lag_seconds'],
            status: HealthStatus::from($data['status']),
            calculatedAt: CarbonImmutable::parse($data['calculated_at']),
        );
    }

    /**
     * Business rule: is self-healing (consumer restart) warranted?
     */
    public function needsSelfHealing(): bool
    {
        return $this->status === HealthStatus::Critical
            && $this->consumersOnline < $this->consumersExpected;
    }

    /**
     * Business rule: is the pipeline fully operational?
     */
    public function isFullyOperational(): bool
    {
        return $this->status === HealthStatus::Healthy
            && $this->consumersOnline >= $this->consumersExpected
            && $this->throughputPerSecond > 0;
    }

    /**
     * Business rule: estimated time to catch up (seconds)
     */
    public function estimatedCatchUpSeconds(): ?float
    {
        if ($this->throughputPerSecond <= 0 || $this->pendingMessages <= 0) {
            return null;
        }

        return round($this->pendingMessages / $this->throughputPerSecond, 2);
    }

    /**
     * Business rule: consumer deficit count
     */
    public function consumerDeficit(): int
    {
        return max(0, $this->consumersExpected - $this->consumersOnline);
    }

    /**
     * Business rule: is the lag growing? (requires previous state for comparison)
     */
    public function isLagWorseThan(self $previous): bool
    {
        return $this->lagSeconds > $previous->lagSeconds;
    }

    public function isHealthy(): bool
    {
        return $this->status === HealthStatus::Healthy;
    }

    public function isDegraded(): bool
    {
        return $this->status === HealthStatus::Degraded;
    }

    public function isCritical(): bool
    {
        return $this->status === HealthStatus::Critical;
    }

    public function toArray(): array
    {
        return [
            'topic' => $this->topic,
            'consumer_lag' => $this->consumerLag,
            'throughput_per_second' => $this->throughputPerSecond,
            'pending_messages' => $this->pendingMessages,
            'consumers_online' => $this->consumersOnline,
            'consumers_expected' => $this->consumersExpected,
            'lag_seconds' => $this->lagSeconds,
            'status' => $this->status->value,
            'needs_self_healing' => $this->needsSelfHealing(),
            'estimated_catch_up_seconds' => $this->estimatedCatchUpSeconds(),
            'consumer_deficit' => $this->consumerDeficit(),
            'calculated_at' => $this->calculatedAt->toIso8601String(),
        ];
    }

    /**
     * Domain invariant validation
     */
    private function validate(): void
    {
        if (trim($this->topic) === '') {
            throw new \InvalidArgumentException('PipelineHealth topic cannot be empty');
        }

        if ($this->throughputPerSecond < 0) {
            throw new \InvalidArgumentException('PipelineHealth throughput must be >= 0');
        }

        if ($this->consumersOnline < 0 || $this->consumersExpected < 0) {
            throw new \InvalidArgumentException('PipelineHealth consumer counts must be >= 0');
        }

        if ($this->lagSeconds < -1) {
            throw new \InvalidArgumentException('PipelineHealth lagSeconds must be >= -1');
        }
    }
}
