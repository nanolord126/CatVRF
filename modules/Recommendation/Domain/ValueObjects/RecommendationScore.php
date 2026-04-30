<?php

declare(strict_types=1);

namespace Modules\Recommendation\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class RecommendationScore
{
    private function __construct(
        private float $value,
        private float $confidence = 1.0,
    ) {
        if ($value < 0.0 || $value > 1.0) {
            throw new InvalidArgumentException('Recommendation score must be between 0.0 and 1.0.');
        }
        if ($confidence < 0.0 || $confidence > 1.0) {
            throw new InvalidArgumentException('Confidence must be between 0.0 and 1.0.');
        }
    }

    public static function fromRaw(float $value, float $confidence = 1.0): self
    {
        return new self($value, $confidence);
    }

    public static function fromMultiObjective(array $objectives): self
    {
        $weightedSum = 0.0;
        $totalWeight = 0.0;
        foreach ($objectives as $name => $config) {
            $weight = $config['weight'] ?? 1.0;
            $score = $config['score'] ?? 0.0;
            $weightedSum += $score * $weight;
            $totalWeight += $weight;
        }
        $normalized = $totalWeight > 0 ? $weightedSum / $totalWeight : 0.0;

        return new self(min(1.0, max(0.0, $normalized)));
    }

    public static function zero(): self
    {
        return new self(0.0, 0.0);
    }

    public function getValue(): float
    {
        return $this->value;
    }

    public function getConfidence(): float
    {
        return $this->confidence;
    }

    public function adjusted(): float
    {
        return $this->value * $this->confidence;
    }

    public function equals(RecommendationScore $other): bool
    {
        return abs($this->value - $other->value) < 0.00001;
    }

    public function toArray(): array
    {
        return [
            'value' => round($this->value, 6),
            'confidence' => round($this->confidence, 6),
            'adjusted' => round($this->adjusted(), 6),
        ];
    }
}
