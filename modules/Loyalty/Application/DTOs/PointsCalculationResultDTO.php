<?php

declare(strict_types=1);

namespace Modules\Loyalty\Application\DTOs;

use Modules\Loyalty\Domain\ValueObjects\Points;

final readonly class PointsCalculationResultDTO
{
    private function __construct(
        public Points $basePoints,
        public Points $bonusPoints,
        public Points $totalPoints,
        public float $multiplier,
        public array $appliedRules,
        public string $description
    ) {
    }

    public static function create(
        Points $basePoints,
        Points $bonusPoints,
        float $multiplier = 1.0,
        array $appliedRules = [],
        string $description = ''
    ): self {
        $totalPoints = $basePoints->add($bonusPoints)->multiply($multiplier);

        return new self(
            basePoints: $basePoints,
            bonusPoints: $bonusPoints,
            totalPoints: $totalPoints,
            multiplier: $multiplier,
            appliedRules: $appliedRules,
            description: $description
        );
    }

    public function toArray(): array
    {
        return [
            'base_points' => $this->basePoints->getValue(),
            'bonus_points' => $this->bonusPoints->getValue(),
            'total_points' => $this->totalPoints->getValue(),
            'multiplier' => $this->multiplier,
            'applied_rules' => $this->appliedRules,
            'description' => $this->description,
        ];
    }
}
