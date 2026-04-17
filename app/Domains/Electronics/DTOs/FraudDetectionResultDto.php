<?php declare(strict_types=1);

namespace App\Domains\Electronics\DTOs;

final readonly class FraudDetectionResultDto
{
    /**
     * @param array<string, mixed> $riskFactors
     * @param array<string, mixed> $mlFeatures
     */
    public function __construct(
        public bool $isFraudulent,
        public float $fraudProbability,
        public string $riskLevel,
        public array $riskFactors,
        public array $mlFeatures,
        public string $correlationId,
        public ?string $recommendedAction = null,
        public ?int $holdDurationMinutes = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'is_fraudulent' => $this->isFraudulent,
            'fraud_probability' => $this->fraudProbability,
            'risk_level' => $this->riskLevel,
            'risk_factors' => $this->riskFactors,
            'ml_features' => $this->mlFeatures,
            'correlation_id' => $this->correlationId,
            'recommended_action' => $this->recommendedAction,
            'hold_duration_minutes' => $this->holdDurationMinutes,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            isFraudulent: $data['is_fraudulent'] ?? false,
            fraudProbability: $data['fraud_probability'] ?? 0.0,
            riskLevel: $data['risk_level'] ?? 'low',
            riskFactors: $data['risk_factors'] ?? [],
            mlFeatures: $data['ml_features'] ?? [],
            correlationId: $data['correlation_id'] ?? '',
            recommendedAction: $data['recommended_action'] ?? null,
            holdDurationMinutes: $data['hold_duration_minutes'] ?? null,
        );
    }
}
