<?php

declare(strict_types=1);

namespace App\Services\ML\DTOs;

use App\Services\ML\Enums\RiskLevel;

final readonly class RiskPrediction
{
    /**
     * @param array<string> $crossAllergies
     * @param array<int, array<string, mixed>> $safeAlternatives
     * @param array<string, mixed> $features
     */
    public function __construct(
        public float $probability,
        public RiskLevel $riskLevel,
        public float $confidence,
        public array $crossAllergies = [],
        public array $safeAlternatives = [],
        public array $features = [],
    ) {
    }

    public function isHighRisk(): bool
    {
        return $this->riskLevel === RiskLevel::High || $this->riskLevel === RiskLevel::Critical;
    }

    public function shouldBlock(): bool
    {
        return $this->riskLevel === RiskLevel::Critical;
    }

    public function toArray(): array
    {
        return [
            'probability' => $this->probability,
            'risk_level' => $this->riskLevel->value,
            'confidence' => $this->confidence,
            'cross_allergies' => $this->crossAllergies,
            'safe_alternatives' => $this->safeAlternatives,
            'features' => $this->features,
        ];
    }
}
