<?php

declare(strict_types=1);

namespace Modules\Marketplace\Domain\ValueObjects;

/**
 * Value Object для денежных сумм
 */
final readonly class Money
{
    private function __construct(
        public float $amount,
        public string $currency,
    ) {
        $this->validate();
    }

    public static function create(float $amount, string $currency = 'RUB'): self
    {
        return new self($amount, $currency);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            amount: (float) $data['amount'],
            currency: $data['currency'],
        );
    }

    public static function zero(string $currency = 'RUB'): self
    {
        return new self(0.0, $currency);
    }

    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'currency' => $this->currency,
        ];
    }

    public function add(Money $other): self
    {
        $this->assertSameCurrency($other);
        return new self($this->amount + $other->amount, $this->currency);
    }

    public function subtract(Money $other): self
    {
        $this->assertSameCurrency($other);
        return new self($this->amount - $other->amount, $this->currency);
    }

    public function multiply(float $multiplier): self
    {
        return new self($this->amount * $multiplier, $this->currency);
    }

    public function divide(float $divisor): self
    {
        if ($divisor === 0.0) {
            throw new \InvalidArgumentException('Cannot divide by zero');
        }
        return new self($this->amount / $divisor, $this->currency);
    }

    public function isZero(): bool
    {
        return abs($this->amount) < 0.01;
    }

    public function isPositive(): bool
    {
        return $this->amount > 0;
    }

    public function isNegative(): bool
    {
        return $this->amount < 0;
    }

    public function isGreaterThan(Money $other): bool
    {
        $this->assertSameCurrency($other);
        return $this->amount > $other->amount;
    }

    public function isLessThan(Money $other): bool
    {
        $this->assertSameCurrency($other);
        return $this->amount < $other->amount;
    }

    public function equals(Money $other): bool
    {
        return $this->currency === $other->currency && abs($this->amount - $other->amount) < 0.01;
    }

    public function format(int $decimals = 2): string
    {
        return number_format($this->amount, $decimals, ',', ' ') . ' ' . $this->currency;
    }

    public function toMinorUnits(): int
    {
        return (int) round($this->amount * 100);
    }

    public static function fromMinorUnits(int $amount, string $currency = 'RUB'): self
    {
        return new self($amount / 100, $currency);
    }

    public function withDiscount(float $percentage): self
    {
        if ($percentage < 0 || $percentage > 100) {
            throw new \InvalidArgumentException('Discount percentage must be between 0 and 100');
        }
        return $this->multiply(1 - $percentage / 100);
    }

    public function withTax(float $percentage): self
    {
        if ($percentage < 0) {
            throw new \InvalidArgumentException('Tax percentage cannot be negative');
        }
        return $this->multiply(1 + $percentage / 100);
    }

    private function assertSameCurrency(Money $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException('Cannot operate on different currencies');
        }
    }

    private function validate(): void
    {
        if ($this->amount < 0) {
            throw new \InvalidArgumentException('Amount cannot be negative');
        }

        if (!preg_match('/^[A-Z]{3}$/', $this->currency)) {
            throw new \InvalidArgumentException('Currency must be a valid ISO 4217 code');
        }
    }
}
