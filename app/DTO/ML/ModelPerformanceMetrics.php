<?php

declare(strict_types=1);

namespace App\DTO\ML;

use Carbon\CarbonImmutable;

/**
 * Model Performance Metrics DTO
 * 
 * Immutable data transfer object for model performance tracking.
 * Used for concept drift detection and model evaluation.
 */
final readonly class ModelPerformanceMetrics
{
    public function __construct(
        public readonly string $modelVersion,
        public readonly string $modelType,
        public readonly float $accuracy,
        public readonly float $precision,
        public readonly float $recall,
        public readonly float $f1Score,
        public readonly float $aucRoc,
        public readonly int $sampleCount,
        public readonly CarbonImmutable $timestamp,
        public readonly ?float $accuracyDecay = null,
        public readonly ?float $f1Decay = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            modelVersion: $data['model_version'],
            modelType: $data['model_type'],
            accuracy: $data['accuracy'],
            precision: $data['precision'],
            recall: $data['recall'],
            f1Score: $data['f1_score'],
            aucRoc: $data['auc_roc'],
            sampleCount: $data['sample_count'],
            timestamp: CarbonImmutable::parse($data['timestamp']),
            accuracyDecay: $data['accuracy_decay'] ?? null,
            f1Decay: $data['f1_decay'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'model_version' => $this->modelVersion,
            'model_type' => $this->modelType,
            'accuracy' => $this->accuracy,
            'precision' => $this->precision,
            'recall' => $this->recall,
            'f1_score' => $this->f1Score,
            'auc_roc' => $this->aucRoc,
            'sample_count' => $this->sampleCount,
            'timestamp' => $this->timestamp->toIso8601String(),
            'accuracy_decay' => $this->accuracyDecay,
            'f1_decay' => $this->f1Decay,
        ];
    }

    public function hasSignificantDecay(float $threshold = 0.08): bool
    {
        return ($this->accuracyDecay !== null && $this->accuracyDecay > $threshold)
            || ($this->f1Decay !== null && $this->f1Decay > $threshold);
    }

    public function isHealthy(): bool
    {
        return $this->accuracy >= 0.85 && $this->f1Score >= 0.80 && !$this->hasSignificantDecay();
    }
}
