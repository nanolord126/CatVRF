<?php

declare(strict_types=1);

namespace App\DTO\ML;

use Carbon\CarbonImmutable;

/**
 * Drift Alert DTO
 * 
 * Immutable data transfer object for drift alerts.
 * Used for notification system when drift exceeds thresholds.
 */
final readonly class DriftAlert
{
    public function __construct(
        public readonly string $alertId,
        public readonly string $severity, // 'WARNING', 'CRITICAL'
        public readonly string $modelType,
        public readonly string $verticalCode,
        public readonly string $featureName,
        public readonly float $driftScore,
        public readonly string $metricType, // 'psi', 'ks', 'js', 'combined'
        public readonly float $threshold,
        public readonly array $affectedFeatures,
        public readonly ?string $recommendedAction,
        public readonly CarbonImmutable $timestamp,
        public readonly string $correlationId,
        public readonly ?array $shapExplanation = null,
    ) {}

    public static function fromDriftResult(DriftResult $result, string $alertType): self
    {
        return new self(
            alertId: 'alert_'.uniqid(),
            severity: $result->isCritical() ? 'CRITICAL' : 'WARNING',
            modelType: $result->modelType,
            verticalCode: $result->verticalCode,
            featureName: $result->featureName,
            driftScore: $result->combinedScore,
            metricType: 'combined',
            threshold: $alertType === 'critical' ? 0.7 : 0.3,
            affectedFeatures: [$result->featureName],
            recommendedAction: $result->isCritical() ? 'retrain_model' : 'monitor_closely',
            timestamp: CarbonImmutable::now(),
            correlationId: $result->correlationId,
            shapExplanation: $result->explanation,
        );
    }

    public function toArray(): array
    {
        return [
            'alert_id' => $this->alertId,
            'severity' => $this->severity,
            'model_type' => $this->modelType,
            'vertical_code' => $this->verticalCode,
            'feature_name' => $this->featureName,
            'drift_score' => $this->driftScore,
            'metric_type' => $this->metricType,
            'threshold' => $this->threshold,
            'affected_features' => $this->affectedFeatures,
            'recommended_action' => $this->recommendedAction,
            'timestamp' => $this->timestamp->toIso8601String(),
            'correlation_id' => $this->correlationId,
            'shap_explanation' => $this->shapExplanation,
        ];
    }

    public function requiresRetrain(): bool
    {
        return $this->severity === 'CRITICAL' && $this->recommendedAction === 'retrain_model';
    }
}
