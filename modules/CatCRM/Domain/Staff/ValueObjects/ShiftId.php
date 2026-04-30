<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Staff\ValueObjects;

/**
 * ShiftId — Value Object для ID смены
 */
final readonly class ShiftId
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

    public function equals(ShiftId $other): bool
    {
        return $this->value === $other->value;
    }
}
