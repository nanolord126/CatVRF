<?php

declare(strict_types=1);

namespace Modules\Bonuses\Domain\ValueObjects;

use DomainException;
use Modules\Bonuses\Domain\Enums\VestingCurveType;

/**
 * ValueObject VestingCurve
 *
 * Represents the vesting curve for a locked bonus batch in CatFloat.
 * Defines how bonuses unlock over time through daily vesting calculations.
 *
 * Vesting curve types:
 * - LINEAR: Equal daily release (6.67% per day for 15 days default)
 * - ACCELERATED: Faster release based on activity/streak (up to 10% per day)
 * - CUSTOM: Custom schedule defined in vesting_schedule JSON for special campaigns
 *
 * Linear vesting:
 * - Default 15-day period
 * - Each day unlocks exactly 1/15 (6.67%) of total amount
 * - Predictable and easy to understand for users
 * - Used for standard bonus awards
 *
 * Accelerated vesting:
 * - Base 15-day period but releases faster
 * - Exponential curve: later days release more
 * - Day 1: ~4%, Day 7: ~6%, Day 15: ~10%
 * - Total still 100% over period
 * - Used for active users with streaks
 *
 * Custom vesting:
 * - Fully customizable schedule
 * - Can have front-loaded or back-loaded releases
 * - Used for special campaigns and promotions
 * - Must sum to exactly 100%
 * - Example: Day 1: 20%, Day 7: 30%, Day 15: 50%
 *
 * Acceleration impact:
 * - Activity (daily threshold): Reduces total days, not curve
 * - Streak milestones: Reduces total days, not curve
 * - Tier (Gold/Platinum): Reduces total days, not curve
 * - Curve type remains the same, period shortens
 *
 * Float yield calculation:
 * - Yield is calculated on average daily locked balance
 * - As bonuses vest, locked balance decreases
 * - Yield decreases proportionally as vesting progresses
 * - Linear curve: linear yield decrease
 * - Accelerated curve: faster yield decrease
 *
 * Cross-vertical multipliers:
 * - Curve type doesn't affect multiplier eligibility
 * - Multipliers apply when bonuses are spent, not when vested
 * - Fully vested bonuses are eligible for cross-vertical bonuses
 *
 * @see Modules\Bonuses\Domain\Enums\VestingCurveType
 * @see Modules\Bonuses\Domain\ValueObjects\HoldPeriod
 */
final readonly class VestingCurve
{
    /**
     * Type of vesting curve.
     */
    private VestingCurveType $type;

    /**
     * Total vesting period in days.
     */
    private int $totalDays;

    /**
     * Custom vesting schedule for CUSTOM type.
     * Array of ['day' => int, 'percentage' => float] entries.
     */
    private ?array $customSchedule;

    /**
     * Private constructor to enforce factory methods.
     */
    private function __construct(
        VestingCurveType $type,
        int $totalDays,
        ?array $customSchedule = null
    ) {
        $this->type = $type;
        $this->totalDays = $totalDays;
        $this->customSchedule = $customSchedule;
        $this->validate();
    }

    /**
     * Validates the vesting curve configuration.
     *
     * @throws DomainException When validation fails.
     */
    private function validate(): void
    {
        if ($this->totalDays <= 0) {
            throw new DomainException('Vesting period must be positive');
        }

        if ($this->totalDays > 365) {
            throw new DomainException('Vesting period cannot exceed 365 days');
        }

        if ($this->type === VestingCurveType::CUSTOM && $this->customSchedule === null) {
            throw new DomainException('Custom schedule required for custom vesting curve');
        }

        if ($this->type === VestingCurveType::CUSTOM) {
            $this->validateCustomSchedule();
        }
    }

    /**
     * Validates custom schedule sums to 100% and has valid entries.
     *
     * @throws DomainException When schedule is invalid.
     */
    private function validateCustomSchedule(): void
    {
        if (empty($this->customSchedule)) {
            throw new DomainException('Custom schedule cannot be empty');
        }

        $totalPercentage = 0.0;
        $days = [];

        foreach ($this->customSchedule as $item) {
            if (!isset($item['day']) || !isset($item['percentage'])) {
                throw new DomainException('Custom schedule items must have day and percentage');
            }

            $day = (int) $item['day'];
            $percentage = (float) $item['percentage'];

            if ($day < 1 || $day > $this->totalDays) {
                throw new DomainException("Custom schedule day {$day} is out of range");
            }

            if (isset($days[$day])) {
                throw new DomainException("Custom schedule has duplicate day {$day}");
            }

            if ($percentage < 0 || $percentage > 100) {
                throw new DomainException("Custom schedule percentage must be between 0 and 100");
            }

            $days[$day] = true;
            $totalPercentage += $percentage;
        }

        if (abs($totalPercentage - 100.0) > 0.01) {
            throw new DomainException(
                "Custom schedule must sum to 100%, currently sums to {$totalPercentage}%"
            );
        }
    }

    /**
     * Creates a linear vesting curve.
     *
     * @param int $totalDays Total vesting period in days (default 15).
     */
    public static function linear(int $totalDays = 15): self
    {
        return new self(VestingCurveType::LINEAR, $totalDays);
    }

    /**
     * Creates an accelerated vesting curve.
     *
     * @param int $totalDays Total vesting period in days (default 15).
     */
    public static function accelerated(int $totalDays = 15): self
    {
        return new self(VestingCurveType::ACCELERATED, $totalDays);
    }

    /**
     * Creates a custom vesting curve with a specific schedule.
     *
     * @param int $totalDays Total vesting period in days.
     * @param array $schedule Array of ['day' => int, 'percentage' => float] entries.
     */
    public static function custom(int $totalDays, array $schedule): self
    {
        return new self(VestingCurveType::CUSTOM, $totalDays, $schedule);
    }

    /**
     * Reconstructs vesting curve from array data.
     *
     * @param array $data Array containing 'type', 'total_days', and optionally 'custom_schedule'.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            type: VestingCurveType::fromString($data['type']),
            totalDays: (int) $data['total_days'],
            customSchedule: $data['custom_schedule'] ?? null
        );
    }

    /**
     * Calculates the unlock percentage for a given day (1-indexed).
     *
     * @param int $day Day number (1 to totalDays).
     * @return float Unlock percentage for that day (0-100).
     */
    public function getUnlockPercentageForDay(int $day): float
    {
        if ($day < 1 || $day > $this->totalDays) {
            return 0.0;
        }

        return match ($this->type) {
            VestingCurveType::LINEAR => 100.0 / $this->totalDays,
            VestingCurveType::ACCELERATED => $this->getAcceleratedPercentage($day),
            VestingCurveType::CUSTOM => $this->getCustomPercentage($day),
        };
    }

    /**
     * Calculates the total unlocked percentage up to a given day.
     *
     * @param int $day Day number (1 to totalDays).
     * @return float Cumulative unlocked percentage (0-100).
     */
    public function getTotalUnlockedPercentage(int $day): float
    {
        $total = 0.0;
        $maxDay = min($day, $this->totalDays);

        for ($i = 1; $i <= $maxDay; $i++) {
            $total += $this->getUnlockPercentageForDay($i);
        }

        return min($total, 100.0);
    }

    /**
     * Calculates the remaining locked percentage at a given day.
     *
     * @param int $day Day number (1 to totalDays).
     * @return float Remaining locked percentage (0-100).
     */
    public function getRemainingLockedPercentage(int $day): float
    {
        return max(0.0, 100.0 - $this->getTotalUnlockedPercentage($day));
    }

    /**
     * Calculates the unlock amount for a given day based on total bonus amount.
     *
     * @param int $totalAmount Total bonus amount in kopecks.
     * @param int $day Day number (1 to totalDays).
     * @return int Unlock amount in kopecks for that day.
     */
    public function getUnlockAmountForDay(int $totalAmount, int $day): int
    {
        $percentage = $this->getUnlockPercentageForDay($day);
        return (int) ($totalAmount * ($percentage / 100));
    }

    /**
     * Calculates the total unlocked amount up to a given day.
     *
     * @param int $totalAmount Total bonus amount in kopecks.
     * @param int $day Day number (1 to totalDays).
     * @return int Cumulative unlocked amount in kopecks.
     */
    public function getTotalUnlockedAmount(int $totalAmount, int $day): int
    {
        $percentage = $this->getTotalUnlockedPercentage($day);
        return (int) ($totalAmount * ($percentage / 100));
    }

    /**
     * Calculates the remaining locked amount at a given day.
     *
     * @param int $totalAmount Total bonus amount in kopecks.
     * @param int $day Day number (1 to totalDays).
     * @return int Remaining locked amount in kopecks.
     */
    public function getRemainingLockedAmount(int $totalAmount, int $day): int
    {
        return $totalAmount - $this->getTotalUnlockedAmount($totalAmount, $day);
    }

    /**
     * Calculates the accelerated percentage for a given day.
     * Uses exponential curve: faster release in later days.
     *
     * @param int $day Day number.
     * @return float Unlock percentage for that day.
     */
    private function getAcceleratedPercentage(int $day): float
    {
        $progress = $day / $this->totalDays;
        $basePercentage = 100.0 / $this->totalDays;
        $multiplier = 1.0 + ($progress * 0.5); // Up to 1.5x faster at end
        return $basePercentage * $multiplier;
    }

    /**
     * Gets the custom percentage for a given day.
     *
     * @param int $day Day number.
     * @return float Unlock percentage for that day (0 if not defined).
     */
    private function getCustomPercentage(int $day): float
    {
        foreach ($this->customSchedule as $scheduleItem) {
            if ($scheduleItem['day'] === $day) {
                return (float) $scheduleItem['percentage'];
            }
        }
        return 0.0;
    }

    /**
     * Checks if vesting is complete for a given day.
     *
     * @param int $day Day number.
     * @return bool True if vesting is complete.
     */
    public function isVestingComplete(int $day): bool
    {
        return $day >= $this->totalDays;
    }

    /**
     * Gets the days remaining until vesting completion.
     *
     * @param int $currentDay Current day number.
     * @return int Days remaining (0 if complete).
     */
    public function getDaysRemaining(int $currentDay): int
    {
        return max(0, $this->totalDays - $currentDay);
    }

    /**
     * Gets the vesting progress as a percentage.
     *
     * @param int $currentDay Current day number.
     * @return float Progress percentage (0-100).
     */
    public function getProgressPercentage(int $currentDay): float
    {
        return ($currentDay / $this->totalDays) * 100;
    }

    /**
     * Gets the type of vesting curve.
     */
    public function getType(): VestingCurveType
    {
        return $this->type;
    }

    /**
     * Gets the total vesting period in days.
     */
    public function getTotalDays(): int
    {
        return $this->totalDays;
    }

    /**
     * Checks if this is a custom vesting curve.
     */
    public function isCustom(): bool
    {
        return $this->type === VestingCurveType::CUSTOM;
    }

    /**
     * Checks if this is an accelerated vesting curve.
     */
    public function isAccelerated(): bool
    {
        return $this->type === VestingCurveType::ACCELERATED;
    }

    /**
     * Converts vesting curve to array for serialization.
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'total_days' => $this->totalDays,
            'custom_schedule' => $this->customSchedule,
            'daily_percentage' => $this->type === VestingCurveType::LINEAR
                ? (100.0 / $this->totalDays)
                : null,
        ];
    }

    /**
     * Generates a daily vesting schedule array.
     *
     * @return array Array of daily unlock percentages.
     */
    public function getDailySchedule(): array
    {
        $schedule = [];
        for ($day = 1; $day <= $this->totalDays; $day++) {
            $schedule[$day] = [
                'day' => $day,
                'percentage' => $this->getUnlockPercentageForDay($day),
                'cumulative' => $this->getTotalUnlockedPercentage($day),
            ];
        }
        return $schedule;
    }
}
