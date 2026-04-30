<?php

declare(strict_types=1);

namespace Modules\Restaurant\Domain\ValueObjects;

final readonly class Money
{
    private const KOPECKS_IN_RUBLE = 100;

    public function __construct(
        public int $kopecks, // Сумма в копейках (безопасное хранение)
    ) {
        if ($kopecks < 0) {
            throw new \InvalidArgumentException('Amount cannot be negative');
        }
    }

    public static function fromRubles(float $rubles): self
    {
        return new self((int) round($rubles * self::KOPECKS_IN_RUBLE));
    }

    public static function fromKopecks(int $kopecks): self
    {
        return new self($kopecks);
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public function toRubles(): float
    {
        return $this->kopecks / self::KOPECKS_IN_RUBLE;
    }

    public function toKopecks(): int
    {
        return $this->kopecks;
    }

    public function add(Money $other): self
    {
        return new self($this->kopecks + $other->kopecks);
    }

    public function subtract(Money $other): self
    {
        $result = $this->kopecks - $other->kopecks;
        if ($result < 0) {
            throw new \InvalidArgumentException('Subtraction result cannot be negative');
        }
        return new self($result);
    }

    public function multiply(float $multiplier): self
    {
        if ($multiplier < 0) {
            throw new \InvalidArgumentException('Multiplier cannot be negative');
        }
        return new self((int) round($this->kopecks * $multiplier));
    }

    public function divide(float $divisor): self
    {
        if ($divisor <= 0) {
            throw new \InvalidArgumentException('Divisor must be positive');
        }
        return new self((int) round($this->kopecks / $divisor));
    }

    public function equals(Money $other): bool
    {
        return $this->kopecks === $other->kopecks;
    }

    public function greaterThan(Money $other): bool
    {
        return $this->kopecks > $other->kopecks;
    }

    public function lessThan(Money $other): bool
    {
        return $this->kopecks < $other->kopecks;
    }

    public function isZero(): bool
    {
        return $this->kopecks === 0;
    }

    public function format(): string
    {
        return number_format($this->toRubles(), 2, ',', ' ') . ' ₽';
    }

    public function __toString(): string
    {
        return $this->format();
    }
}
