<?php

declare(strict_types=1);

namespace Modules\Dental\Domain\ValueObjects;

final readonly class PatientId
{
    private function __construct(public int $value)
    {
    }

    public static function fromInt(int $value): self
    {
        return new self($value);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
