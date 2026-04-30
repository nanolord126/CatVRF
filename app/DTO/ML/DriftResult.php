<?php

declare(strict_types=1);

namespace App\DTO\ML;

use Carbon\CarbonImmutable;

/**
 * Drift Result DTO
 * 
 * Immutable data transfer object for drift detection results.
 * Contains all metrics from drift analysis including PSI, KS-test, JS divergence.
 */
final readonly class DriftResult
{
    public function __construct(
        public readonly float $combinedScore,
        public readonly bool $driftDetected,
        public readonly string $severity, // 'LOW', 'MEDIUM', 'HIGH'
        public readonly array $metrics,
        public readonly string $featureName,
        public readonly string $modelType,
        public readonly string $verticalCode,
        public readonly string $correlationId,
        public readonly CarbonImmutable $timestamp,
        public readonly ?array $explanation = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            combinedScore: $data['combined_score'],
            driftDetected: $data['drift_detected'],
            severity: $data['severity'],
            metrics: $data['metrics'],
            featureName: $data['feature_name'],
            modelType: $data['model_type'],
            verticalCode: $data['vertical_code'],
            correlationId: $data['correlation_id'],
            timestamp: CarbonImmutable::parse($data['timestamp']),
            explanation: $data['explanation'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'combined_score' => $this->combinedScore,
            'drift_detected' => $this->driftDetected,
            'severity' => $this->severity,
            'metrics' => $this->metrics,
            'feature_name' => $this->featureName,
            'model_type' => $this->modelType,
            'vertical_code' => $this->verticalCode,
            'correlation_id' => $this->correlationId,
            'timestamp' => $this->timestamp->toIso8601String(),
            'explanation' => $this->explanation,
        ];
    }

    public function isCritical(): bool
    {
        return $this->severity === 'HIGH' && $this->driftDetected;
    }

    public function isWarning(): bool
    {
        return $this->severity === 'MEDIUM' && $this->driftDetected;
    }
}
