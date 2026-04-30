<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Staff\ValueObjects;

/**
 * EmployeeId — Value Object для ID сотрудника
 */
final readonly class EmployeeId
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

    public function equals(EmployeeId $other): bool
    {
        return $this->value === $other->value;
    }
}
