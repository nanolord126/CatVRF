<?php

declare(strict_types=1);

namespace App\Services\Personalization\DTOs;

final readonly class Recommendation
{
    /**
     * @param array<string, mixed> $explanation
     */
    public function __construct(
        public int $id,
        public string $type,
        public string $name,
        public float $score,
        public float $confidence,
        public string $vertical,
        public array $explanation = [],
    ) {
    }

    public function isHighConfidence(): bool
    {
        return $this->confidence >= 0.8;
    }

    public function isMediumConfidence(): bool
    {
        return $this->confidence >= 0.5 && $this->confidence < 0.8;
    }

    public function isLowConfidence(): bool
    {
        return $this->confidence < 0.5;
    }

    public function getPrimaryReason(): string
    {
        return $this->explanation['reason'] ?? 'personalized';
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'name' => $this->name,
            'score' => $this->score,
            'confidence' => $this->confidence,
            'vertical' => $this->vertical,
            'explanation' => $this->explanation,
        ];
    }
}
