<?php

declare(strict_types=1);

namespace Modules\RealEstate\Domain\ValueObjects;

final readonly class Price
{
    public function __construct(
        public float $value,
        public string $currency = 'USD',
    ) {
        if ($this->value < 0) {
            throw new \InvalidArgumentException('Price cannot be negative');
        }

        if (empty($this->currency)) {
            throw new \InvalidArgumentException('Currency cannot be empty');
        }
    }

    public function __toString(): string
    {
        return number_format($this->value, 2) . ' ' . $this->currency;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value && $this->currency === $other->currency;
    }

    public function add(self $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException('Cannot add prices with different currencies');
        }

        return new self($this->value + $other->value, $this->currency);
    }

    public function multiply(float $multiplier): self
    {
        return new self($this->value * $multiplier, $this->currency);
    }
}
