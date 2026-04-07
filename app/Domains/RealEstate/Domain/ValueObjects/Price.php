<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Domain\ValueObjects;

use InvalidArgumentException;

/**
 * Immutable monetary value object.
 * All amounts are stored in kopecks (integer) to avoid floating-point errors.
 */
final class Price
{
    public function __construct(
        private int $amountKopecks,
        private string $currency = 'RUB') {
        if ($amountKopecks < 0) {
            throw new InvalidArgumentException(
                "Price amount cannot be negative, got {$amountKopecks} kopecks."
            );
        }
    }

    public static function fromRubles(float $rubles, string $currency = 'RUB'): self
    {
        return new self((int) round($rubles * 100), $currency);
    }

    public static function fromKopecks(int $kopecks, string $currency = 'RUB'): self
    {
        return new self($kopecks, $currency);
    }

    public function getAmountKopecks(): int
    {
        return $this->amountKopecks;
    }

    public function getAmountRubles(): float
    {
        return $this->amountKopecks / 100.0;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function add(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amountKopecks + $other->amountKopecks, $this->currency);
    }

    public function subtract(self $other): self
    {
        $this->assertSameCurrency($other);

        $result = $this->amountKopecks - $other->amountKopecks;

        if ($result < 0) {
            throw new \DomainException('Subtraction would result in a negative price.');
        }

        return new self($result, $this->currency);
    }

    public function percentage(float $percent): self
    {
        return new self((int) round($this->amountKopecks * $percent / 100.0), $this->currency);
    }

    public function equals(self $other): bool
    {
        return $this->amountKopecks === $other->amountKopecks
            && $this->currency === $other->currency;
    }

    public function isGreaterThan(self $other): bool
    {
        $this->assertSameCurrency($other);

        return $this->amountKopecks > $other->amountKopecks;
    }

    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new \DomainException(
                "Currency mismatch: {$this->currency} vs {$other->currency}."
            );
        }
    }
}
