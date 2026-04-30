<?php

declare(strict_types=1);

namespace Modules\BigData\Domain\Events;

use Carbon\CarbonImmutable;
use Modules\BigData\Domain\Enums\DriftStatus;

/**
 * CLV Drift Detected Domain Event
 *
 * Dispatched when CLV model drift crosses a significant threshold.
 * Triggers model retraining scheduling and notification.
 */
final class CLVDriftDetected
{
    public readonly CarbonImmutable $occurredAt;

    public function __construct(
        public readonly string $modelVersion,
        public readonly DriftStatus $driftStatus,
        public readonly float $accuracyDrop,
        public readonly float $psiValue,
        public readonly bool $requiresRetraining,
        public readonly ?string $correlationId = null,
        ?CarbonImmutable $occurredAt = null,
    ) {
        $this->occurredAt = $occurredAt ?? CarbonImmutable::now();
    }

    /**
     * Should this trigger immediate retraining?
     */
    public function shouldRetrainImmediately(): bool
    {
        return $this->driftStatus->requiresRetraining();
    }

    /**
     * Should this schedule a retraining job?
     */
    public function shouldScheduleRetraining(): bool
    {
        return $this->driftStatus->requiresScheduledRetraining();
    }

    /**
     * Generate notification text
     */
    public function toNotificationText(): string
    {
        $emoji = $this->requiresRetraining ? '🚨' : '⚠️';
        $dropPercent = round($this->accuracyDrop * 100, 1);

        return "{$emoji} CLV Model Drift [{$this->driftStatus->value}]: " .
            "accuracy drop {$dropPercent}%, PSI={$this->psiValue}, " .
            "version={$this->modelVersion}";
    }

    public function toArray(): array
    {
        return [
            'event' => 'clv_drift_detected',
            'model_version' => $this->modelVersion,
            'drift_status' => $this->driftStatus->value,
            'accuracy_drop' => $this->accuracyDrop,
            'accuracy_drop_percent' => round($this->accuracyDrop * 100, 1),
            'psi_value' => $this->psiValue,
            'requires_retraining' => $this->requiresRetraining,
            'correlation_id' => $this->correlationId,
            'occurred_at' => $this->occurredAt->toIso8601String(),
        ];
    }
}
