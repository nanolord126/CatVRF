<?php

declare(strict_types=1);

namespace App\Domains\Bonuses\ValueObjects;

/**
 * VestingCurve - ValueObject for bonus vesting curves
 * 
 * Defines different vesting strategies for locked bonuses.
 * Default is LINEAR_15_DAYS (6.67% per day for 15 days).
 */
final readonly class VestingCurve
{
    private const LINEAR_15_DAYS = 'linear_15_days';
    private const LINEAR_30_DAYS = 'linear_30_days';
    private const LINEAR_7_DAYS = 'linear_7_days';
    private const CLIFF_7_DAYS_LINEAR_8_DAYS = 'cliff_7_linear_8';
    private const BACKLOADED = 'backloaded';
    private const FRONTLOADED = 'frontloaded';

    private string $type;
    private int $days;
    private float $dailyRate;

    private function __construct(string $type, int $days, float $dailyRate)
    {
        $this->type = $type;
        $this->days = $days;
        $this->dailyRate = $dailyRate;
    }

    public static function linear15Days(): self
    {
        return new self(self::LINEAR_15_DAYS, 15, 0.0667);
    }

    public static function linear30Days(): self
    {
        return new self(self::LINEAR_30_DAYS, 30, 0.0333);
    }

    public static function linear7Days(): self
    {
        return new self(self::LINEAR_7_DAYS, 7, 0.1429);
    }

    public static function cliff7DaysLinear8Days(): self
    {
        return new self(self::CLIFF_7_DAYS_LINEAR_8_DAYS, 15, 0.0);
    }

    public static function backloaded(): self
    {
        return new self(self::BACKLOADED, 15, 0.0);
    }

    public static function frontloaded(): self
    {
        return new self(self::FRONTLOADED, 15, 0.0);
    }

    public static function fromString(string $type): self
    {
        return match ($type) {
            self::LINEAR_15_DAYS => self::linear15Days(),
            self::LINEAR_30_DAYS => self::linear30Days(),
            self::LINEAR_7_DAYS => self::linear7Days(),
            self::CLIFF_7_DAYS_LINEAR_8_DAYS => self::cliff7DaysLinear8Days(),
            self::BACKLOADED => self::backloaded(),
            self::FRONTLOADED => self::frontloaded(),
            default => throw new \InvalidArgumentException("Invalid vesting curve type: {$type}"),
        };
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getDays(): int
    {
        return $this->days;
    }

    public function getDailyRate(): float
    {
        return $this->dailyRate;
    }

    public function getUnlockPercentageForDay(int $day): float
    {
        if ($day < 1 || $day > $this->days) {
            return 0.0;
        }

        return match ($this->type) {
            self::LINEAR_15_DAYS, self::LINEAR_30_DAYS, self::LINEAR_7_DAYS => $this->dailyRate * 100,
            self::CLIFF_7_DAYS_LINEAR_8_DAYS => $day <= 7 ? 0.0 : (12.5),
            self::BACKLOADED => $this->calculateBackloadedPercentage($day),
            self::FRONTLOADED => $this->calculateFrontloadedPercentage($day),
            default => $this->dailyRate * 100,
        };
    }

    private function calculateBackloadedPercentage(int $day): float
    {
        // More unlock in later days
        $totalDays = $this->days;
        $weight = ($day / $totalDays) ** 2;
        return ($weight / $totalDays) * 100;
    }

    private function calculateFrontloadedPercentage(int $day): float
    {
        // More unlock in earlier days
        $totalDays = $this->days;
        $weight = (1 - (($day - 1) / $totalDays)) ** 2;
        return ($weight / $totalDays) * 100;
    }

    public function getCumulativeUnlockPercentage(int $day): float
    {
        $cumulative = 0.0;
        for ($i = 1; $i <= $day; $i++) {
            $cumulative += $this->getUnlockPercentageForDay($i);
        }

        return min(100.0, $cumulative);
    }

    public function equals(VestingCurve $other): bool
    {
        return $this->type === $other->type;
    }

    public function isLinear(): bool
    {
        return in_array($this->type, [
            self::LINEAR_15_DAYS,
            self::LINEAR_30_DAYS,
            self::LINEAR_7_DAYS,
        ], true);
    }

    public function hasCliff(): bool
    {
        return $this->type === self::CLIFF_7_DAYS_LINEAR_8_DAYS;
    }

    public function __toString(): string
    {
        return $this->type;
    }
}
