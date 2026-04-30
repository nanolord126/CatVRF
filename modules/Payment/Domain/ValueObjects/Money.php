<?php

declare(strict_types=1);

namespace Modules\Payment\Domain\ValueObjects;

use InvalidArgumentException;

/**
 * Money Value Object - immutable representation of monetary amounts.
 *
 * All amounts are stored in kopecks (1/100 of currency unit) to avoid floating-point issues.
 */
final readonly class Money
{
    private const int KOPECKS_PER_UNIT = 100;

    public function __construct(
        public int $amount,
        public string $currency = 'RUB',
    ) {
        if ($amount < 0) {
            throw new InvalidArgumentException('Amount cannot be negative');
        }

        if (strlen($currency) !== 3) {
            throw new InvalidArgumentException('Currency must be 3-letter ISO code');
        }
    }

    public static function fromDecimal(float $amount, string $currency = 'RUB'): self
    {
        return new self(
            (int) round($amount * self::KOPECKS_PER_UNIT),
            $currency,
        );
    }

    public static function fromMajorUnits(int $amount, string $currency = 'RUB'): self
    {
        return new self($amount * self::KOPECKS_PER_UNIT, $currency);
    }

    public function toKopecks(): int
    {
        return $this->amount;
    }

    public function toMajorUnits(): float
    {
        return $this->amount / self::KOPECKS_PER_UNIT;
    }

    public function add(Money $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException('Cannot add different currencies');
        }

        return new self($this->amount + $other->amount, $this->currency);
    }

    public function subtract(Money $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException('Cannot subtract different currencies');
        }

        return new self($this->amount - $other->amount, $this->currency);
    }

    public function multiply(float $multiplier): self
    {
        return new self((int) round($this->amount * $multiplier), $this->currency);
    }
}
