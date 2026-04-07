<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class Price
{
    /**
     * @param int $amountInCents
     * @param string $currency
     */
    private function __construct(
        private int $amountInCents,
        private string $currency = 'RUB'
    ) {
        $this->ensureAmountIsPositive($amountInCents);
        $this->ensureCurrencyIsSupported($currency);
    }

    /**
     * @param int $amount
     * @return void
     */
    private function ensureAmountIsPositive(int $amount): void
    {
        if ($amount < 0) {
            throw new InvalidArgumentException('Price amount cannot be negative.');
        }
    }

    /**
     * @param string $currency
     * @return void
     */
    private function ensureCurrencyIsSupported(string $currency): void
    {
        if ($currency !== 'RUB') {
            throw new InvalidArgumentException('Only RUB currency is supported.');
        }
    }

    /**
     * @param int $amountInCents
     * @param string $currency
     * @return static
     */
    public static function fromCents(int $amountInCents, string $currency = 'RUB'): self
    {
        return new self($amountInCents, $currency);
    }

    /**
     * @param float $amountInRubles
     * @param string $currency
     * @return static
     */
    public static function fromRubles(float $amountInRubles, string $currency = 'RUB'): self
    {
        return new self((int)($amountInRubles * 100), $currency);
    }

    /**
     * @return int
     */
    public function getAmountInCents(): int
    {
        return $this->amountInCents;
    }

    /**
     * @return float
     */
    public function getAmountInRubles(): float
    {
        return $this->amountInCents / 100;
    }

    /**
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * @param self $other
     * @return bool
     */
    public function equals(self $other): bool
    {
        return $this->amountInCents === $other->amountInCents && $this->currency === $other->currency;
    }

    /**
     * @return string
     */
    public function getFormatted(): string
    {
        return number_format($this->getAmountInRubles(), 2, ',', ' ') . ' ' . $this->currency;
    }
}
