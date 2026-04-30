<?php

declare(strict_types=1);

namespace App\Domains\Payment\ValueObjects;

use InvalidArgumentException;

/**
 * Money Value Object - immutable representation of monetary amounts.
 *
 * All amounts are stored in kopecks (1/100 of currency unit) to avoid floating-point issues.
 * Supports arithmetic operations, comparison, and formatting.
 *
 * @property-read int $amount Amount in kopecks
 * @property-read string $currency Currency code (ISO 4217)
 */
final readonly class MoneyVO
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

    /**
     * Create from decimal amount (e.g., 100.50 RUB).
     */
    public static function fromDecimal(float $amount, string $currency = 'RUB'): self
    {
        return new self(
            (int) round($amount * self::KOPECKS_PER_UNIT),
            $currency,
        );
    }

    /**
     * Create from amount in major units (e.g., 100 RUB = 10000 kopecks).
     */
    public static function fromMajorUnits(int $amount, string $currency = 'RUB'): self
    {
        return new self($amount * self::KOPECKS_PER_UNIT, $currency);
    }

    /**
     * Get amount in decimal format (e.g., 10050 kopecks = 100.50).
     */
    public function toDecimal(): float
    {
        return $this->amount / self::KOPECKS_PER_UNIT;
    }

    /**
     * Get amount in major units (e.g., 10050 kopecks = 100).
     */
    public function toMajorUnits(): int
    {
        return (int) floor($this->amount / self::KOPECKS_PER_UNIT);
    }

    /**
     * Get amount in kopecks.
     */
    public function toKopecks(): int
    {
        return $this->amount;
    }

    /**
     * Add another money value.
     */
    public function add(self $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException('Cannot add different currencies');
        }

        return new self($this->amount + $other->amount, $this->currency);
    }

    /**
     * Subtract another money value.
     */
    public function subtract(self $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException('Cannot subtract different currencies');
        }

        $newAmount = $this->amount - $other->amount;
        if ($newAmount < 0) {
            throw new InvalidArgumentException('Result cannot be negative');
        }

        return new self($newAmount, $this->currency);
    }

    /**
     * Multiply by a factor.
     */
    public function multiply(float $factor): self
    {
        if ($factor < 0) {
            throw new InvalidArgumentException('Factor cannot be negative');
        }

        return new self((int) round($this->amount * $factor), $this->currency);
    }

    /**
     * Calculate percentage of this amount.
     */
    public function percentage(float $percent): self
    {
        return $this->multiply($percent / 100);
    }

    /**
     * Check if this amount is greater than another.
     */
    public function greaterThan(self $other): bool
    {
        $this->assertSameCurrency($other);

        return $this->amount > $other->amount;
    }

    /**
     * Check if this amount is less than another.
     */
    public function lessThan(self $other): bool
    {
        $this->assertSameCurrency($other);

        return $this->amount < $other->amount;
    }

    /**
     * Check if this amount equals another.
     */
    public function equals(self $other): bool
    {
        return $this->amount === $other->amount && $this->currency === $other->currency;
    }

    /**
     * Check if amount is zero.
     */
    public function isZero(): bool
    {
        return $this->amount === 0;
    }

    /**
     * Check if amount is positive.
     */
    public function isPositive(): bool
    {
        return $this->amount > 0;
    }

    /**
     * Format as string for display (e.g., "100.50 ₽").
     */
    public function format(string $locale = 'ru_RU'): string
    {
        $decimal = $this->toDecimal();
        $symbol = match ($this->currency) {
            'RUB' => '₽',
            'USD' => '$',
            'EUR' => '€',
            default => $this->currency,
        };

        return number_format($decimal, 2, ',', ' ').' '.$symbol;
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return [
            'amount_kopecks' => $this->amount,
            'amount_decimal' => $this->toDecimal(),
            'currency' => $this->currency,
            'formatted' => $this->format(),
        ];
    }

    /**
     * Create from array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['amount_kopecks'] ?? (int) round(($data['amount_decimal'] ?? 0) * self::KOPECKS_PER_UNIT),
            $data['currency'] ?? 'RUB',
        );
    }

    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException('Cannot compare different currencies');
        }
    }
}
