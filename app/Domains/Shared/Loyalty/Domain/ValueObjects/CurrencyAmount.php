<?php

declare(strict_types=1);

namespace Modules\Loyalty\Domain\ValueObjects;

use DomainException;

final readonly class CurrencyAmount
{
    private float $value;

    public function __construct(float $value)
    {
        if ($value < 0) {
            throw new DomainException('Currency amount cannot be negative');
        }

        $this->value = round($value, 2);
    }

    public static function zero(): self
    {
        return new self(0.0);
    }

    public static function fromFloat(float $value): self
    {
        return new self($value);
    }

    public function getValue(): float
    {
        return $this->value;
    }

    public function isZero(): bool
    {
        return $this->value === 0.0;
    }

    public function add(CurrencyAmount $other): CurrencyAmount
    {
        return new self($this->value + $other->value);
    }

    public function subtract(CurrencyAmount $other): CurrencyAmount
    {
        if ($other->value > $this->value) {
            throw new DomainException('Cannot subtract more than available');
        }

        return new self($this->value - $other->value);
    }

    public function multiply(float $multiplier): CurrencyAmount
    {
        if ($multiplier < 0) {
            throw new DomainException('Multiplier cannot be negative');
        }

        return new self($this->value * $multiplier);
    }

    public function toFloat(): float
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
