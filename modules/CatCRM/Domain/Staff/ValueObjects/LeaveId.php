<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Staff\ValueObjects;

/**
 * LeaveId — Value Object для ID отпуска
 */
final readonly class LeaveId
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

    public function equals(LeaveId $other): bool
    {
        return $this->value === $other->value;
    }
}
