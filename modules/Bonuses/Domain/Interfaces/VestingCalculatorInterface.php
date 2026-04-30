<?php

declare(strict_types=1);

namespace Modules\Bonuses\Domain\Interfaces;

use DateTimeImmutable;
use Modules\Bonuses\Domain\Entities\LockedBonusBatch;
use Modules\Bonuses\Domain\ValueObjects\HoldPeriod;
use Modules\Bonuses\Domain\ValueObjects\VestingCurve;

/**
 * Interface: VestingCalculatorInterface
 *
 * Defines the contract for calculating vesting schedules and applying vesting to locked bonus batches.
 * Implementations handle the core vesting logic for the CatFloat system.
 *
 * Vesting calculation responsibilities:
 * - Calculate daily unlock amounts based on vesting curve type
 * - Apply hold period accelerations from activity, streaks, and tier
 * - Track cumulative vesting progress over time
 * - Determine when vesting is complete
 * - Handle accelerated vesting scenarios
 *
 * Vesting curve types:
 * - LINEAR: Equal daily release (6.67% per day for 15 days default)
 * - ACCELERATED: Faster release based on activity/streak (up to 10% per day)
 * - CUSTOM: Custom schedule defined in vesting_schedule JSON
 *
 * Hold period acceleration:
 * - Activity: -1 day per day of meeting 30+ point threshold
 * - Streak: -3 days at 7 days, -5 days at 14 days, -8 days at 30+ days
 * - Tier: Gold B2B -5 days, Platinum B2B -8 days
 * - All accelerators stack additively
 * - Minimum hold: 1 day (except legendary status: instant)
 *
 * Implementation requirements:
 * - Must be idempotent (same inputs = same outputs)
 * - Must handle edge cases (leap years, timezone changes, etc.)
 * - Must validate all inputs before calculation
 * - Must throw DomainException for invalid operations
 * - Must log all calculations with correlation ID
 *
 * Performance considerations:
 * - Calculations should be O(1) for single day vesting
 * - Batch calculations should be optimized for bulk operations
 * - Cache vesting schedules where appropriate
 * - Use Redis for real-time hold period updates
 *
 * Compliance:
 * - All calculations logged with correlation ID
 * - Audit trail for all vesting changes
 * - PII anonymized in external logs (152-ФЗ)
 *
 * @see Modules\Bonuses\Domain\Entities\LockedBonusBatch
 * @see Modules\Bonuses\Domain\ValueObjects\VestingCurve
 * @see Modules\Bonuses\Domain\ValueObjects\HoldPeriod
 */
interface VestingCalculatorInterface
{
    /**
     * Calculates the daily unlock amount for a given day.
     *
     * @param LockedBonusBatch $batch The bonus batch.
     * @param int $day Day number (1-indexed).
     * @return int Unlock amount in kopecks.
     * @throws DomainException If day is out of range.
     */
    public function calculateDailyUnlock(LockedBonusBatch $batch, int $day): int;

    /**
     * Calculates the cumulative unlocked amount up to a given day.
     *
     * @param LockedBonusBatch $batch The bonus batch.
     * @param int $day Day number (1-indexed).
     * @return int Cumulative unlocked amount in kopecks.
     */
    public function calculateCumulativeUnlock(LockedBonusBatch $batch, int $day): int;

    /**
     * Calculates the remaining locked amount at a given day.
     *
     * @param LockedBonusBatch $batch The bonus batch.
     * @param int $day Day number (1-indexed).
     * @return int Remaining locked amount in kopecks.
     */
    public function calculateRemainingLocked(LockedBonusBatch $batch, int $day): int;

    /**
     * Calculates the vesting progress as a percentage.
     *
     * @param LockedBonusBatch $batch The bonus batch.
     * @return float Progress percentage (0-100).
     */
    public function calculateVestingProgress(LockedBonusBatch $batch): float;

    /**
     * Checks if vesting is complete for a batch.
     *
     * @param LockedBonusBatch $batch The bonus batch.
     * @param DateTimeImmutable $now Current timestamp.
     * @return bool True if vesting is complete.
     */
    public function isVestingComplete(LockedBonusBatch $batch, DateTimeImmutable $now): bool;

    /**
     * Gets the days remaining until vesting completion.
     *
     * @param LockedBonusBatch $batch The bonus batch.
     * @param DateTimeImmutable $now Current timestamp.
     * @return int Days remaining (0 if complete).
     */
    public function getDaysRemaining(LockedBonusBatch $batch, DateTimeImmutable $now): int;

    /**
     * Applies hold period acceleration to a batch.
     *
     * @param LockedBonusBatch $batch The bonus batch.
     * @param int $activityReduction Days reduced from activity.
     * @param int $streakReduction Days reduced from streak.
     * @param int $tierReduction Days reduced from tier.
     * @return HoldPeriod Updated hold period with accelerations.
     */
    public function applyHoldAcceleration(
        LockedBonusBatch $batch,
        int $activityReduction,
        int $streakReduction,
        int $tierReduction
    ): HoldPeriod;

    /**
     * Generates a daily vesting schedule for a batch.
     *
     * @param LockedBonusBatch $batch The bonus batch.
     * @return array Array of daily vesting data.
     */
    public function generateVestingSchedule(LockedBonusBatch $batch): array;

    /**
     * Calculates the effective hold period after all accelerations.
     *
     * @param HoldPeriod $holdPeriod The hold period.
     * @param int $baseHoldDays Base hold period.
     * @return int Effective hold period in days.
     */
    public function calculateEffectiveHoldPeriod(HoldPeriod $holdPeriod, int $baseHoldDays): int;

    /**
     * Validates a vesting curve configuration.
     *
     * @param VestingCurve $curve The vesting curve.
     * @return bool True if valid.
     * @throws DomainException If invalid.
     */
    public function validateVestingCurve(VestingCurve $curve): bool;

    /**
     * Estimates the expected unlock date for a batch.
     *
     * @param LockedBonusBatch $batch The bonus batch.
     * @return DateTimeImmutable Expected unlock date.
     */
    public function estimateUnlockDate(LockedBonusBatch $batch): DateTimeImmutable;
}
