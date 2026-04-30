<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Domain\ValueObjects;

final readonly class Money
{
    public function __construct(
        public float $amount,
        public string $currency = 'RUB',
    ) {
        if ($amount < 0) {
            throw new \InvalidArgumentException('Amount cannot be negative');
        }
    }

    public static function fromFloat(float $amount, string $currency = 'RUB'): self
    {
        return new self($amount, $currency);
    }

    public static function zero(string $currency = 'RUB'): self
    {
        return new self(0.0, $currency);
    }

    public function add(Money $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException('Cannot add different currencies');
        }
        return new self($this->amount + $other->amount, $this->currency);
    }

    public function subtract(Money $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException('Cannot subtract different currencies');
        }
        return new self(max(0, $this->amount - $other->amount), $this->currency);
    }

    public function multiply(float $multiplier): self
    {
        return new self($this->amount * $multiplier, $this->currency);
    }

    public function isGreaterThan(Money $other): bool
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException('Cannot compare different currencies');
        }
        return $this->amount > $other->amount;
    }

    public function isLessThan(Money $other): bool
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException('Cannot compare different currencies');
        }
        return $this->amount < $other->amount;
    }

    public function equals(Money $other): bool
    {
        return $this->currency === $other->currency && abs($this->amount - $other->amount) < 0.01;
    }

    public function format(): string
    {
        return number_format($this->amount, 2, ',', ' ') . ' ' . $this->currency;
    }

    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'currency' => $this->currency,
        ];
    }
}
