<?php

declare(strict_types=1);

namespace Modules\Payments\Domain\ValueObjects;

use InvalidArgumentException;

/**
 * Value Object: Денежная сумма (в копейках).
 * Иммутабельный объект.
 */
final readonly class Money
{
    public function __construct(
        public int $amount,
        public string $currency = 'RUB',
    ) {
        if ($amount < 0) {
            throw new InvalidArgumentException("Amount cannot be negative: {$amount}");
        }

        if (empty($currency) || strlen($currency) !== 3) {
            throw new InvalidArgumentException("Invalid currency: {$currency}");
        }
    }

    public static function ofRubles(float $rubles, string $currency = 'RUB'): self
    {
        return new self((int) round($rubles * 100), $currency);
    }

    public static function ofKopeks(int $kopeks, string $currency = 'RUB'): self
    {
        return new self($kopeks, $currency);
    }

    public function add(self $other): self
    {
        $this->assertSameCurrency($other);
        return new self($this->amount + $other->amount, $this->currency);
    }

    public function subtract(self $other): self
    {
        $this->assertSameCurrency($other);
        if ($other->amount > $this->amount) {
            throw new InvalidArgumentException('Insufficient funds for subtraction');
        }
        return new self($this->amount - $other->amount, $this->currency);
    }

    public function isGreaterThan(self $other): bool
    {
        $this->assertSameCurrency($other);
        return $this->amount > $other->amount;
    }

    public function isLessThan(self $other): bool
    {
        $this->assertSameCurrency($other);
        return $this->amount < $other->amount;
    }

    public function equals(self $other): bool
    {
        return $this->amount === $other->amount && $this->currency === $other->currency;
    }

    public function toRubles(): float
    {
        return $this->amount / 100;
    }

    public function __toString(): string
    {
        return "{$this->toRubles()} {$this->currency}";
    }

    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException(
                "Currency mismatch: {$this->currency} vs {$other->currency}"
            );
        }
    }
}
