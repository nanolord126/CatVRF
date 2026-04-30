<?php

declare(strict_types=1);

namespace Modules\Dental\Domain\ValueObjects;

final readonly class Money
{
    private function __construct(
        public int $amount, // stored in cents/kopecks
        public string $currency = 'RUB',
    ) {
    }

    public static function fromDecimal(float $amount, string $currency = 'RUB'): self
    {
        return new self((int) round($amount * 100), $currency);
    }

    public static function zero(string $currency = 'RUB'): self
    {
        return new self(0, $currency);
    }

    public function toDecimal(): float
    {
        return $this->amount / 100;
    }

    public function add(self $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException('Cannot add money with different currencies');
        }

        return new self($this->amount + $other->amount, $this->currency);
    }

    public function subtract(self $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException('Cannot subtract money with different currencies');
        }

        return new self($this->amount - $other->amount, $this->currency);
    }

    public function multiply(float $factor): self
    {
        return new self((int) round($this->amount * $factor), $this->currency);
    }

    public function isZero(): bool
    {
        return $this->amount === 0;
    }

    public function isPositive(): bool
    {
        return $this->amount > 0;
    }

    public function equals(self $other): bool
    {
        return $this->amount === $other->amount && $this->currency === $other->currency;
    }
}
