<?php

declare(strict_types=1);

namespace Modules\Wallet\Domain\ValueObjects;

use InvalidArgumentException;

/**
 * Value Object: Денежная сумма кошелька (копейки).
 */
final readonly class Money
{
    public function __construct(
        public int    $amount,
        public string $currency = 'RUB',
    ) {
        if ($amount < 0) {
            throw new InvalidArgumentException("Wallet amount cannot be negative: {$amount}");
        }
    }

    public static function ofKopeks(int $kopeks, string $currency = 'RUB'): self
    {
        return new self($kopeks, $currency);
    }

    public function add(self $other): self
    {
        return new self($this->amount + $other->amount, $this->currency);
    }

    public function subtract(self $other): self
    {
        if ($other->amount > $this->amount) {
            throw new \Modules\Wallet\Domain\Exceptions\InsufficientFundsException(
                "Insufficient funds: have {$this->amount}, need {$other->amount}"
            );
        }
        return new self($this->amount - $other->amount, $this->currency);
    }

    public function isGreaterThan(self $other): bool
    {
        return $this->amount > $other->amount;
    }

    public function toKopeks(): int
    {
        return $this->amount;
    }

    public function toRubles(): float
    {
        return $this->amount / 100;
    }

    public function __toString(): string
    {
        return "{$this->toRubles()} {$this->currency}";
    }
}
