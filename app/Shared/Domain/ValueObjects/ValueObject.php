<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObjects;

abstract class ValueObject
{
    abstract public function value(): mixed;

    public function equals(self $other): bool
    {
        return static::class === get_class($other)
            && $this->value() === $other->value();
    }

    public function __toString(): string
    {
        return (string) $this->value();
    }
}
