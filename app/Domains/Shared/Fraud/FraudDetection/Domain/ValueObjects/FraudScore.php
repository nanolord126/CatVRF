<?php

declare(strict_types=1);

namespace Modules\FraudDetection\Domain\ValueObjects;

use Illuminate\Contracts\Support\Arrayable;

final readonly class FraudScore implements Arrayable
{
    public function __construct(
        private float $score,
        private string $model_version
    ) {
        if ($this->score < 0.0 || $this->score > 1.0) {
            throw new \InvalidArgumentException('Fraud score must be between 0 and 1.');
        }
    }

    public function getScore(): float
    {
        return $this->score;
    }

    public function getModelVersion(): string
    {
        return $this->model_version;
    }

    public function isFraudulent(float $threshold = 0.85): bool
    {
        return $this->score >= $threshold;
    }

    public function toArray(): array
    {
        return [
            'score' => $this->score,
            'model_version' => $this->model_version,
        ];
    }
}
