<?php

declare(strict_types=1);

namespace Modules\Bonuses\Domain\ValueObjects;

use DomainException;
use DateTimeImmutable;

/**
 * ValueObject HoldPeriod
 *
 * Represents the hold period for a locked bonus batch in CatFloat.
 * Manages the base hold period and all acceleration sources that reduce the hold time.
 *
 * Hold period mechanics:
 * - Base hold: Default 15 days for B2C, 10 days for Gold/Platinum B2B
 * - Accelerators reduce the hold period but cannot make it less than 1 day
 * - All accelerators stack additively
 * - Acceleration is applied at batch creation and can be updated dynamically
 *
 * Acceleration sources:
 * 1. Activity acceleration:
 *    - Daily login + 3+ actions (30+ activity score): -1 day
 *    - Maximum: -1 day per day of activity
 *    - Applied daily when activity threshold is met
 *
 * 2. Streak acceleration:
 *    - Streak 7 days: -3 days
 *    - Streak 14 days: -5 days
 *    - Streak 30 days: Unlock in 7 days instead of 15 (-8 days)
 *    - Streak 60+ days: Further reduction
 *    - Milestone-based, not cumulative per day
 *
 * 3. Tier acceleration:
 *    - Standard B2C: 0 days acceleration
 *    - Gold B2B: -5 days (base 10 days instead of 15)
 *    - Platinum B2B: -8 days (base 7 days instead of 15)
 *    - Applied at batch creation based on user tier
 *
 * Instant unlock:
 * - User can pay 99 ₽ to unlock 1000 bonuses instantly
 * - Creates a new batch with 1-day hold period
 * - Platform earns micro-transaction revenue
 * - Available as Instant Unlock Boost in marketplace
 *
 * Hold period calculation:
 * - actual_hold_days = base_days - (activity + streak + tier accelerations)
 * - Minimum: 1 day (except legendary status: instant unlock)
 * - Maximum: base_days (no negative acceleration)
 *
 * Float yield impact:
 * - Longer hold = more yield for platform
 * - Shorter hold = less yield but higher user satisfaction
 * - Accelerated vesting reduces total yield
 * - Platform must balance user experience with revenue
 *
 * Cross-vertical bonuses:
 * - Hold period doesn't affect cross-vertical multiplier eligibility
 * - Multipliers apply when bonuses are spent, not when they unlock
 * - Fully unlocked bonuses are immediately eligible for multipliers
 *
 * Compliance:
 * - Hold period changes logged with correlation ID
 * - Audit trail for all acceleration applications
 * - User notified of hold period changes
 * - 152-ФЗ compliant: no PII in external logs
 *
 * @see Modules\Bonuses\Domain\ValueObjects\VestingCurve
 * @see Modules\Bonuses\Domain\ValueObjects\StreakCount
 */
final readonly class HoldPeriod
{
    /**
     * Base hold period in days before accelerators.
     * Default 15 days for B2C, 10 days for Gold/Platinum B2B.
     */
    private int $baseDays;

    /**
     * Days reduced by daily activity (login + 3+ actions).
     * Maximum -1 day per day of activity.
     */
    private int $activityAccelerationDays;

    /**
     * Days reduced by streak milestones.
     * Milestone-based: 7 days = -3, 14 days = -5, 30 days = -8.
     */
    private int $streakAccelerationDays;

    /**
     * Days reduced by user tier (Gold/Platinum B2B).
     * Applied at batch creation based on user tier.
     */
    private int $tierAccelerationDays;

    /**
     * Timestamp when the hold period started.
     */
    private DateTimeImmutable $startDate;

    /**
     * Private constructor to enforce factory methods.
     */
    private function __construct(
        int $baseDays,
        int $activityAccelerationDays,
        int $streakAccelerationDays,
        int $tierAccelerationDays,
        DateTimeImmutable $startDate
    ) {
        $this->baseDays = $baseDays;
        $this->activityAccelerationDays = $activityAccelerationDays;
        $this->streakAccelerationDays = $streakAccelerationDays;
        $this->tierAccelerationDays = $tierAccelerationDays;
        $this->startDate = $startDate;
        $this->validate();
    }

    /**
     * Validates the hold period configuration.
     *
     * @throws DomainException When validation fails.
     */
    private function validate(): void
    {
        if ($this->baseDays <= 0) {
            throw new DomainException('Base hold period must be positive');
        }

        if ($this->baseDays > 365) {
            throw new DomainException('Base hold period cannot exceed 365 days');
        }

        if ($this->activityAccelerationDays < 0) {
            throw new DomainException('Activity acceleration cannot be negative');
        }

        if ($this->streakAccelerationDays < 0) {
            throw new DomainException('Streak acceleration cannot be negative');
        }

        if ($this->tierAccelerationDays < 0) {
            throw new DomainException('Tier acceleration cannot be negative');
        }

        if ($this->getTotalAccelerationDays() > $this->baseDays) {
            throw new DomainException('Total acceleration cannot exceed base days');
        }
    }

    /**
     * Creates a new hold period with base days.
     *
     * @param int $baseDays Base hold period in days.
     * @param DateTimeImmutable $startDate Start timestamp.
     */
    public static function create(int $baseDays, DateTimeImmutable $startDate): self
    {
        return new self(
            baseDays: $baseDays,
            activityAccelerationDays: 0,
            streakAccelerationDays: 0,
            tierAccelerationDays: 0,
            startDate: $startDate
        );
    }

    /**
     * Creates a hold period for B2C user (15-day base).
     */
    public static function forB2C(DateTimeImmutable $startDate): self
    {
        return self::create(15, $startDate);
    }

    /**
     * Creates a hold period for Gold B2B user (10-day base).
     */
    public static function forGoldB2B(DateTimeImmutable $startDate): self
    {
        return new self(
            baseDays: 10,
            activityAccelerationDays: 0,
            streakAccelerationDays: 0,
            tierAccelerationDays: 5,
            startDate: $startDate
        );
    }

    /**
     * Creates a hold period for Platinum B2B user (7-day base).
     */
    public static function forPlatinumB2B(DateTimeImmutable $startDate): self
    {
        return new self(
            baseDays: 7,
            activityAccelerationDays: 0,
            streakAccelerationDays: 0,
            tierAccelerationDays: 8,
            startDate: $startDate
        );
    }

    /**
     * Creates an instant unlock hold period (1 day).
     */
    public static function instant(DateTimeImmutable $startDate): self
    {
        return self::create(1, $startDate);
    }

    /**
     * Reconstructs hold period from array data.
     *
     * @param array $data Array containing hold period data.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            baseDays: (int) $data['base_days'],
            activityAccelerationDays: (int) ($data['activity_acceleration_days'] ?? 0),
            streakAccelerationDays: (int) ($data['streak_acceleration_days'] ?? 0),
            tierAccelerationDays: (int) ($data['tier_acceleration_days'] ?? 0),
            startDate: new DateTimeImmutable($data['start_date'])
        );
    }

    /**
     * Gets the total acceleration days from all sources.
     */
    public function getTotalAccelerationDays(): int
    {
        return $this->activityAccelerationDays +
               $this->streakAccelerationDays +
               $this->tierAccelerationDays;
    }

    /**
     * Gets the actual hold period after applying all accelerators.
     * Minimum 1 day to prevent instant unlock (except explicit instant unlock).
     */
    public function getActualHoldDays(): int
    {
        $actual = $this->baseDays - $this->getTotalAccelerationDays();
        return max(1, $actual);
    }

    /**
     * Gets the hold period reduction as a percentage of base period.
     */
    public function getReductionPercentage(): float
    {
        if ($this->baseDays === 0) {
            return 0.0;
        }
        return ($this->getTotalAccelerationDays() / $this->baseDays) * 100;
    }

    /**
     * Gets the end date of the hold period.
     */
    public function getEndDate(): DateTimeImmutable
    {
        $interval = new \DateInterval(sprintf('P%dD', $this->getActualHoldDays()));
        return $this->startDate->add($interval);
    }

    /**
     * Checks if the hold period has ended.
     */
    public function hasEnded(DateTimeImmutable $now): bool
    {
        return $now >= $this->getEndDate();
    }

    /**
     * Gets the days elapsed since start.
     */
    public function getDaysElapsed(DateTimeImmutable $now): int
    {
        $interval = $this->startDate->diff($now);
        return $interval->days;
    }

    /**
     * Gets the remaining days in hold period.
     */
    public function getRemainingDays(DateTimeImmutable $now): int
    {
        $remaining = $this->getActualHoldDays() - $this->getDaysElapsed($now);
        return max(0, $remaining);
    }

    /**
     * Gets the hold progress as a percentage.
     */
    public function getProgressPercentage(DateTimeImmutable $now): float
    {
        if ($this->getActualHoldDays() === 0) {
            return 100.0;
        }
        return ($this->getDaysElapsed($now) / $this->getActualHoldDays()) * 100;
    }

    /**
     * Checks if this is an instant unlock (1-day hold).
     */
    public function isInstant(): bool
    {
        return $this->getActualHoldDays() === 1 && $this->baseDays > 1;
    }

    /**
     * Checks if this has tier-based acceleration.
     */
    public function hasTierAcceleration(): bool
    {
        return $this->tierAccelerationDays > 0;
    }

    /**
     * Checks if this has streak-based acceleration.
     */
    public function hasStreakAcceleration(): bool
    {
        return $this->streakAccelerationDays > 0;
    }

    /**
     * Checks if this has activity-based acceleration.
     */
    public function hasActivityAcceleration(): bool
    {
        return $this->activityAccelerationDays > 0;
    }

    /**
     * Creates a new hold period with added activity acceleration.
     */
    public function withActivityAcceleration(int $days): self
    {
        return new self(
            baseDays: $this->baseDays,
            activityAccelerationDays: $this->activityAccelerationDays + $days,
            streakAccelerationDays: $this->streakAccelerationDays,
            tierAccelerationDays: $this->tierAccelerationDays,
            startDate: $this->startDate
        );
    }

    /**
     * Creates a new hold period with added streak acceleration.
     */
    public function withStreakAcceleration(int $days): self
    {
        return new self(
            baseDays: $this->baseDays,
            activityAccelerationDays: $this->activityAccelerationDays,
            streakAccelerationDays: $this->streakAccelerationDays + $days,
            tierAccelerationDays: $this->tierAccelerationDays,
            startDate: $this->startDate
        );
    }

    /**
     * Creates a new hold period with added tier acceleration.
     */
    public function withTierAcceleration(int $days): self
    {
        return new self(
            baseDays: $this->baseDays,
            activityAccelerationDays: $this->activityAccelerationDays,
            streakAccelerationDays: $this->streakAccelerationDays,
            tierAccelerationDays: $this->tierAccelerationDays + $days,
            startDate: $this->startDate
        );
    }

    /**
     * Gets the base hold period.
     */
    public function getBaseDays(): int
    {
        return $this->baseDays;
    }

    /**
     * Gets the activity acceleration days.
     */
    public function getActivityAccelerationDays(): int
    {
        return $this->activityAccelerationDays;
    }

    /**
     * Gets the streak acceleration days.
     */
    public function getStreakAccelerationDays(): int
    {
        return $this->streakAccelerationDays;
    }

    /**
     * Gets the tier acceleration days.
     */
    public function getTierAccelerationDays(): int
    {
        return $this->tierAccelerationDays;
    }

    /**
     * Gets the start date.
     */
    public function getStartDate(): DateTimeImmutable
    {
        return $this->startDate;
    }

    /**
     * Converts hold period to array for serialization.
     */
    public function toArray(): array
    {
        return [
            'base_days' => $this->baseDays,
            'activity_acceleration_days' => $this->activityAccelerationDays,
            'streak_acceleration_days' => $this->streakAccelerationDays,
            'tier_acceleration_days' => $this->tierAccelerationDays,
            'total_acceleration_days' => $this->getTotalAccelerationDays(),
            'actual_hold_days' => $this->getActualHoldDays(),
            'reduction_percentage' => $this->getReductionPercentage(),
            'start_date' => $this->startDate->format('Y-m-d H:i:s'),
            'end_date' => $this->getEndDate()->format('Y-m-d H:i:s'),
            'is_instant' => $this->isInstant(),
            'has_tier_acceleration' => $this->hasTierAcceleration(),
            'has_streak_acceleration' => $this->hasStreakAcceleration(),
            'has_activity_acceleration' => $this->hasActivityAcceleration(),
        ];
    }
}
