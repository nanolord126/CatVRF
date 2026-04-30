<?php

declare(strict_types=1);

namespace App\Domains\Common\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class Price
{
    public function __construct(
        public float $amount,
        public string $currency,
    ) {
        if ($amount < 0) {
            throw new InvalidArgumentException('Price cannot be negative');
        }

        if (empty($currency) || strlen($currency) !== 3) {
            throw new InvalidArgumentException('Currency must be a 3-letter ISO code');
        }
    }

    public static function fromFloat(float $amount, string $currency = 'RUB'): self
    {
        return new self($amount, $currency);
    }

    public static function fromInt(int $amountInCents, string $currency = 'RUB'): self
    {
        return new self($amountInCents / 100, $currency);
    }

    public function toInt(): int
    {
        return (int) round($this->amount * 100);
    }

    public function add(Price $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException('Cannot add prices with different currencies');
        }

        return new self($this->amount + $other->amount, $this->currency);
    }

    public function multiply(float $multiplier): self
    {
        return new self($this->amount * $multiplier, $this->currency);
    }

    public function isGreaterThan(Price $other): bool
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException('Cannot compare prices with different currencies');
        }

        return $this->amount > $other->amount;
    }

    public function isLessThan(Price $other): bool
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException('Cannot compare prices with different currencies');
        }

        return $this->amount < $other->amount;
    }

    public function equals(Price $other): bool
    {
        return $this->amount === $other->amount && $this->currency === $other->currency;
    }

    public function format(int $decimals = 2): string
    {
        return number_format($this->amount, $decimals, '.', ' ') . ' ' . $this->currency;
    }

    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'currency' => $this->currency,
            'amount_in_cents' => $this->toInt(),
        ];
    }
}
