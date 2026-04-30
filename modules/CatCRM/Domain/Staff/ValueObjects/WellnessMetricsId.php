<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Staff\ValueObjects;

/**
 * WellnessMetricsId — Value Object для ID метрик благополучия
 */
final readonly class WellnessMetricsId
{
    public function __construct(
        public int $value,
    ) {}

    public static function fromInt(int $value): self
    {
        return new self($value);
    }

    public function toInt(): int
    {
        return $this->value;
    }

    public function equals(WellnessMetricsId $other): bool
    {
        return $this->value === $other->value;
    }
}
