<?php

declare(strict_types=1);

namespace App\Domains\Common\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class Quantity
{
    public function __construct(
        public int $value,
        public ?string $unit = null,
    ) {
        if ($value < 0) {
            throw new InvalidArgumentException('Quantity cannot be negative');
        }

        if ($value > PHP_INT_MAX - 1) {
            throw new InvalidArgumentException('Quantity exceeds maximum value');
        }
    }

    public static function fromInt(int $value, ?string $unit = null): self
    {
        return new self($value, $unit);
    }

    public static function zero(?string $unit = null): self
    {
        return new self(0, $unit);
    }

    public function isZero(): bool
    {
        return $this->value === 0;
    }

    public function isPositive(): bool
    {
        return $this->value > 0;
    }

    public function add(Quantity $other): self
    {
        if ($this->unit !== $other->unit) {
            throw new InvalidArgumentException('Cannot add quantities with different units');
        }

        return new self($this->value + $other->value, $this->unit);
    }

    public function subtract(Quantity $other): self
    {
        if ($this->unit !== $other->unit) {
            throw new InvalidArgumentException('Cannot subtract quantities with different units');
        }

        $result = $this->value - $other->value;

        if ($result < 0) {
            throw new InvalidArgumentException('Result cannot be negative');
        }

        return new self($result, $this->unit);
    }

    public function isGreaterThan(Quantity $other): bool
    {
        if ($this->unit !== $other->unit) {
            throw new InvalidArgumentException('Cannot compare quantities with different units');
        }

        return $this->value > $other->value;
    }

    public function isLessThan(Quantity $other): bool
    {
        if ($this->unit !== $other->unit) {
            throw new InvalidArgumentException('Cannot compare quantities with different units');
        }

        return $this->value < $other->value;
    }

    public function equals(Quantity $other): bool
    {
        return $this->value === $other->value && $this->unit === $other->unit;
    }

    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'unit' => $this->unit,
        ];
    }
}
