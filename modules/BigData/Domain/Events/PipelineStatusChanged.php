<?php

declare(strict_types=1);

namespace Modules\BigData\Domain\Events;

use Carbon\CarbonImmutable;
use Modules\BigData\Domain\Enums\HealthStatus;

/**
 * Pipeline Status Changed Domain Event
 *
 * Dispatched when pipeline health status transitions
 * (e.g., healthy → degraded, degraded → critical, critical → healthy).
 */
final class PipelineStatusChanged
{
    public readonly CarbonImmutable $occurredAt;

    public function __construct(
        public readonly string $topic,
        public readonly HealthStatus $previousStatus,
        public readonly HealthStatus $currentStatus,
        public readonly float $lagSeconds,
        public readonly float $throughputPerSecond,
        public readonly ?string $correlationId = null,
        ?CarbonImmutable $occurredAt = null,
    ) {
        $this->occurredAt = $occurredAt ?? CarbonImmutable::now();
    }

    /**
     * Is this a degradation (status got worse)?
     */
    public function isDegradation(): bool
    {
        $severity = [
            HealthStatus::Healthy->value => 0,
            HealthStatus::Degraded->value => 1,
            HealthStatus::Critical->value => 2,
            HealthStatus::Unknown->value => 3,
        ];

        return ($severity[$this->currentStatus->value] ?? 0) > ($severity[$this->previousStatus->value] ?? 0);
    }

    /**
     * Is this a recovery (status improved)?
     */
    public function isRecovery(): bool
    {
        return !$this->isDegradation()
            && $this->currentStatus !== $this->previousStatus
            && $this->currentStatus !== HealthStatus::Unknown;
    }

    /**
     * Is this a transition to critical?
     */
    public function isCriticalTransition(): bool
    {
        return $this->currentStatus === HealthStatus::Critical;
    }

    public function toArray(): array
    {
        return [
            'event' => 'pipeline_status_changed',
            'topic' => $this->topic,
            'previous_status' => $this->previousStatus->value,
            'current_status' => $this->currentStatus->value,
            'is_degradation' => $this->isDegradation(),
            'is_recovery' => $this->isRecovery(),
            'lag_seconds' => $this->lagSeconds,
            'throughput_per_second' => $this->throughputPerSecond,
            'correlation_id' => $this->correlationId,
            'occurred_at' => $this->occurredAt->toIso8601String(),
        ];
    }
}
