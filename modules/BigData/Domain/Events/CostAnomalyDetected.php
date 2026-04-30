<?php

declare(strict_types=1);

namespace Modules\BigData\Domain\Events;

use Modules\BigData\Domain\Enums\CostAnomalyType;
use Modules\BigData\Domain\Enums\AlertSeverity;

/**
 * Cost Anomaly Detected Event
 *
 * Dispatched when a cost anomaly is detected (spike, storage growth, seller overuse).
 * Triggers FinOps investigation and alerts.
 */
final readonly class CostAnomalyDetected
{
    public function __construct(
        public string $anomalyId,
        public CostAnomalyType $anomalyType,
        public AlertSeverity $severity,
        public float $expectedValue,
        public float $actualValue,
        public float $deviationPercent,
        public string $service,
        public int $sellerId,
        public string $description,
    ) {}

    public function toNotification(): array
    {
        return [
            'event' => 'cost_anomaly_detected',
            'anomaly_id' => $this->anomalyId,
            'anomaly_type' => $this->anomalyType->value,
            'severity' => $this->severity->value,
            'expected_value' => round($this->expectedValue, 2),
            'actual_value' => round($this->actualValue, 2),
            'deviation_percent' => round($this->deviationPercent, 1),
            'service' => $this->service,
            'seller_id' => $this->sellerId,
            'description' => $this->description,
            'message' => "Cost anomaly [{$this->anomalyType->value}]: {$this->description} (expected \${$this->expectedValue}, actual \${$this->actualValue}, +{$this->deviationPercent}%)",
        ];
    }
}
