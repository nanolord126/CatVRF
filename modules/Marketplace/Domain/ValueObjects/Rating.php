<?php

declare(strict_types=1);

namespace Modules\Marketplace\Domain\ValueObjects;

/**
 * Value Object для рейтинга
 */
final readonly class Rating
{
    private function __construct(
        public float $value,
        public int $count,
    ) {
        $this->validate();
    }

    public static function create(float $value, int $count = 0): self
    {
        return new self($value, $count);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            value: (float) $data['value'],
            count: (int) $data['count'],
        );
    }

    public static function fromAggregate(array $ratings): self
    {
        if (empty($ratings)) {
            return new self(0.0, 0);
        }

        $sum = array_sum($ratings);
        $count = count($ratings);
        $average = $sum / $count;

        return new self(round($average, 1), $count);
    }

    public static function unknown(): self
    {
        return new self(0.0, 0);
    }

    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'count' => $this->count,
        ];
    }

    public function withNewRating(float $rating): self
    {
        $total = $this->value * $this->count + $rating;
        $newCount = $this->count + 1;
        $newValue = $total / $newCount;

        return new self(round($newValue, 1), $newCount);
    }

    public function withRemovedRating(float $rating): self
    {
        if ($this->count <= 1) {
            return new self(0.0, 0);
        }

        $total = $this->value * $this->count - $rating;
        $newCount = $this->count - 1;
        $newValue = $total / $newCount;

        return new self(round($newValue, 1), $newCount);
    }

    public function isExcellent(): bool
    {
        return $this->value >= 4.5;
    }

    public function isGood(): bool
    {
        return $this->value >= 4.0 && $this->value < 4.5;
    }

    public function isAverage(): bool
    {
        return $this->value >= 3.0 && $this->value < 4.0;
    }

    public function isPoor(): bool
    {
        return $this->value < 3.0;
    }

    public function hasEnoughRatings(int $minCount = 10): bool
    {
        return $this->count >= $minCount;
    }

    public function getStars(): int
    {
        return (int) round($this->value);
    }

    public function getPercentage(): int
    {
        return (int) round(($this->value / 5.0) * 100);
    }

    private function validate(): void
    {
        if ($this->value < 0 || $this->value > 5) {
            throw new \InvalidArgumentException('Rating value must be between 0 and 5');
        }

        if ($this->count < 0) {
            throw new \InvalidArgumentException('Rating count cannot be negative');
        }

        if ($this->count === 0 && $this->value !== 0.0) {
            throw new \InvalidArgumentException('Rating value must be 0 when count is 0');
        }
    }
}
