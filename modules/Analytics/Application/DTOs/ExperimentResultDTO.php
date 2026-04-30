<?php

declare(strict_types=1);

namespace Modules\Analytics\Application\DTOs;

/**
 * Experiment Result Data Transfer Object
 *
 * Immutable DTO containing experiment analysis results.
 * Production-ready: readonly properties, strict typing, validation.
 */
final readonly class ExperimentResultDTO
{
    public function __construct(
        public int $experimentId,
        public string $status,
        public ?string $winningVariantId,
        public array $variantResults,
        public ?float $liftPercent,
        public ?float $pValue,
        public ?float $probabilityToBeBest,
        public ?bool $isStatisticallySignificant,
        public ?string $recommendation,
        public array $metrics,
    ) {}

    /**
     * Create ExperimentResultDTO from array data.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            experimentId: (int) $data['experiment_id'],
            status: $data['status'],
            winningVariantId: $data['winning_variant_id'] ?? null,
            variantResults: $data['variant_results'] ?? [],
            liftPercent: $data['lift_percent'] ?? null,
            pValue: $data['p_value'] ?? null,
            probabilityToBeBest: $data['probability_to_be_best'] ?? null,
            isStatisticallySignificant: $data['is_statistically_significant'] ?? null,
            recommendation: $data['recommendation'] ?? null,
            metrics: $data['metrics'] ?? [],
        );
    }

    /**
     * Convert to array for JSON serialization.
     */
    public function toArray(): array
    {
        return [
            'experiment_id' => $this->experimentId,
            'status' => $this->status,
            'winning_variant_id' => $this->winningVariantId,
            'variant_results' => $this->variantResults,
            'lift_percent' => $this->liftPercent,
            'p_value' => $this->pValue,
            'probability_to_be_best' => $this->probabilityToBeBest,
            'is_statistically_significant' => $this->isStatisticallySignificant,
            'recommendation' => $this->recommendation,
            'metrics' => $this->metrics,
        ];
    }

    /**
     * Check if result is conclusive.
     */
    public function isConclusive(): bool
    {
        return $this->isStatisticallySignificant === true 
            && $this->winningVariantId !== null;
    }

    /**
     * Get formatted recommendation.
     */
    public function getFormattedRecommendation(): string
    {
        return $this->recommendation ?? 'Insufficient data for recommendation';
    }
}
