<?php

declare(strict_types=1);

namespace App\Domains\Bonuses\ValueObjects;

/**
 * StreakLevel - ValueObject for user streak levels
 * 
 * Defines streak levels with corresponding multipliers.
 * Used for gamification and bonus acceleration.
 */
final readonly class StreakLevel
{
    private const BRONZE = 'bronze';
    private const SILVER = 'silver';
    private const GOLD = 'gold';
    private const PLATINUM = 'platinum';
    private const DIAMOND = 'diamond';

    private string $level;
    private int $minDays;
    private int $maxDays;
    private float $multiplier;
    private string $color;

    private function __construct(string $level, int $minDays, int $maxDays, float $multiplier, string $color)
    {
        $this->level = $level;
        $this->minDays = $minDays;
        $this->maxDays = $maxDays;
        $this->multiplier = $multiplier;
        $this->color = $color;
    }

    public static function bronze(): self
    {
        return new self(self::BRONZE, 0, 6, 1.0, '#cd7f32');
    }

    public static function silver(): self
    {
        return new self(self::SILVER, 7, 13, 1.5, '#c0c0c0');
    }

    public static function gold(): self
    {
        return new self(self::GOLD, 14, 20, 2.0, '#ffd700');
    }

    public static function platinum(): self
    {
        return new self(self::PLATINUM, 21, 29, 3.0, '#e5e4e2');
    }

    public static function diamond(): self
    {
        return new self(self::DIAMOND, 30, PHP_INT_MAX, 4.0, '#b9f2ff');
    }

    public static function fromDays(int $days): self
    {
        return match (true) {
            $days >= 30 => self::diamond(),
            $days >= 21 => self::platinum(),
            $days >= 14 => self::gold(),
            $days >= 7 => self::silver(),
            default => self::bronze(),
        };
    }

    public static function fromString(string $level): self
    {
        return match ($level) {
            self::BRONZE => self::bronze(),
            self::SILVER => self::silver(),
            self::GOLD => self::gold(),
            self::PLATINUM => self::platinum(),
            self::DIAMOND => self::diamond(),
            default => throw new \InvalidArgumentException("Invalid streak level: {$level}"),
        };
    }

    public function getLevel(): string
    {
        return $this->level;
    }

    public function getMinDays(): int
    {
        return $this->minDays;
    }

    public function getMaxDays(): int
    {
        return $this->maxDays;
    }

    public function getMultiplier(): float
    {
        return $this->multiplier;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function getAccelerationBonus(): int
    {
        // Additional acceleration days based on streak level
        return match ($this->level) {
            self::BRONZE => 0,
            self::SILVER => 1,
            self::GOLD => 2,
            self::PLATINUM => 3,
            self::DIAMOND => 5,
        };
    }

    public function equals(StreakLevel $other): bool
    {
        return $this->level === $other->level;
    }

    public function isBronze(): bool
    {
        return $this->level === self::BRONZE;
    }

    public function isSilver(): bool
    {
        return $this->level === self::SILVER;
    }

    public function isGold(): bool
    {
        return $this->level === self::GOLD;
    }

    public function isPlatinum(): bool
    {
        return $this->level === self::PLATINUM;
    }

    public function isDiamond(): bool
    {
        return $this->level === self::DIAMOND;
    }

    public function isPremium(): bool
    {
        return in_array($this->level, [self::GOLD, self::PLATINUM, self::DIAMOND], true);
    }

    public function getNextLevel(): ?self
    {
        return match ($this->level) {
            self::BRONZE => self::silver(),
            self::SILVER => self::gold(),
            self::GOLD => self::platinum(),
            self::PLATINUM => self::diamond(),
            self::DIAMOND => null,
        };
    }

    public function getDaysToNextLevel(): ?int
    {
        $next = $this->getNextLevel();
        if ($next === null) {
            return null;
        }

        return $next->minDays - $this->minDays;
    }

    public function __toString(): string
    {
        return ucfirst($this->level);
    }
}
