<?php

declare(strict_types=1);

namespace Modules\Supermarket\Domain\ValueObjects;

final readonly class Money
{
    private function __construct(
        public int $amount, // в копейках/минимальных единицах
        public string $currency = 'RUB',
    ) {}

    public static function fromFloat(float $amount, string $currency = 'RUB'): self
    {
        return new self(
            amount: (int) round($amount * 100),
            currency: $currency,
        );
    }

    public static function fromInt(int $amount, string $currency = 'RUB'): self
    {
        return new self(
            amount: $amount,
            currency: $currency,
        );
    }

    public static function zero(string $currency = 'RUB'): self
    {
        return new self(
            amount: 0,
            currency: $currency,
        );
    }

    public function toFloat(): float
    {
        return $this->amount / 100;
    }

    public function toInt(): int
    {
        return $this->amount;
    }

    public function add(Money $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException('Cannot add money with different currencies');
        }

        return new self(
            amount: $this->amount + $other->amount,
            currency: $this->currency,
        );
    }

    public function subtract(Money $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException('Cannot subtract money with different currencies');
        }

        return new self(
            amount: $this->amount - $other->amount,
            currency: $this->currency,
        );
    }

    public function multiply(float $multiplier): self
    {
        return new self(
            amount: (int) round($this->amount * $multiplier),
            currency: $this->currency,
        );
    }

    public function isGreaterThan(Money $other): bool
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException('Cannot compare money with different currencies');
        }

        return $this->amount > $other->amount;
    }

    public function isLessThan(Money $other): bool
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException('Cannot compare money with different currencies');
        }

        return $this->amount < $other->amount;
    }

    public function equals(Money $other): bool
    {
        return $this->currency === $other->currency && $this->amount === $other->amount;
    }

    public function isZero(): bool
    {
        return $this->amount === 0;
    }

    public function isPositive(): bool
    {
        return $this->amount > 0;
    }

    public function isNegative(): bool
    {
        return $this->amount < 0;
    }

    public function format(): string
    {
        return number_format($this->toFloat(), 2, ',', ' ') . ' ' . $this->currency;
    }

    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'currency' => $this->currency,
            'float' => $this->toFloat(),
        ];
    }
}
