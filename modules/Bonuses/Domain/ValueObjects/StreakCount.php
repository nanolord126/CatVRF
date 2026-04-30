<?php

declare(strict_types=1);

namespace Modules\Bonuses\Domain\ValueObjects;

use DomainException;

/**
 * ValueObject StreakCount
 *
 * Represents a user's streak count for daily activity in CatFloat.
 * Streaks are a core retention mechanic that drives daily user engagement through gamification.
 *
 * Streak mechanics:
 * - Streak increments when user meets daily activity threshold (3+ actions + login)
 * - Streak resets to 0 if user misses a day (no login or no threshold)
 * - Multiplier grows exponentially with streak length
 * - Hold reduction increases at specific milestones
 *
 * Streak milestones and rewards:
 * - Day 1-3: ×1.0 multiplier, no hold reduction
 * - Day 7: ×1.8 multiplier, -3 days hold reduction, +100 instant bonuses
 * - Day 14: ×2.7 multiplier, -5 days hold reduction, +250 instant bonuses
 * - Day 30: ×4.0 multiplier, unlock in 7 days instead of 15, +500 bonuses + badge
 * - Day 60: ×4.5 multiplier, unlock in 5 days instead of 15, +750 bonuses + exclusive quests
 * - Day 90: ×5.0 multiplier, unlock in 3 days instead of 15, +1000 bonuses + premium badge
 * - Day 180: ×6.0 multiplier, instant unlock (1 day), +2000 bonuses + legendary badge
 * - Day 365: ×8.0 multiplier, all bonuses instant, +5000 bonuses + legendary status
 *
 * Multiplier calculation:
 * - Base multiplier: 1.0x
 * - Streak multiplier: 1.0x - 8.0x (based on current streak)
 * - Quest multiplier: 1.0x - 1.5x (from completed quests)
 * - Tier multiplier: 1.0x - 1.2x (Gold/Platinum users)
 * - Maximum effective: 1.0 × 8.0 × 1.5 × 1.2 = 14.4x
 *
 * Hold reduction mechanics:
 * - Reduction applies to all active locked batches
 * - Reduction is applied pro-rata across batches
 * - Cannot reduce hold below 1 day (except legendary status)
 * - Streak reduction stacks with activity and quest reductions
 *
 * Streak reset conditions:
 * - User does not login on a given day
 * - User logs in but doesn't meet activity threshold (30+ points)
 * - Manual reset by admin (fraud investigation, user request)
 *
 * Streak recovery:
 * - After reset, user starts from day 1
 * - Previous longest streak is preserved for achievements
 * - Total active days continue to accumulate
 * - Multiplier resets to 1.0x
 *
 * Engagement impact:
 * - Streaks create daily habit formation
 * - Users with 7+ day streaks show 3.2x higher retention
 * - Users with 30+ day streaks show 5.8x higher retention
 * - Streak breaks cause 40% of users to churn within 7 days
 *
 * Compliance:
 * - All streak changes logged with correlation ID
 * - Audit trail for streak resets and modifications
 * - User notified of streak milestones and breaks
 * - 152-ФЗ compliant: no PII in external logs
 *
 * @see Modules\Bonuses\Domain\ValueObjects\ActivityScore
 * @see Modules\Bonuses\Domain\ValueObjects\HoldPeriod
 */
final readonly class StreakCount
{
    /**
     * Current consecutive days of activity.
     */
    private int $currentDays;

    /**
     * Longest streak ever achieved by this user.
     */
    private int $longestDays;

    /**
     * Total active days in user's history.
     */
    private int $totalActiveDays;

    /**
     * Private constructor to enforce factory methods.
     */
    private function __construct(
        int $currentDays,
        int $longestDays,
        int $totalActiveDays
    ) {
        $this->currentDays = $currentDays;
        $this->longestDays = $longestDays;
        $this->totalActiveDays = $totalActiveDays;
        $this->validate();
    }

    /**
     * Validates the streak count configuration.
     *
     * @throws DomainException When validation fails.
     */
    private function validate(): void
    {
        if ($this->currentDays < 0) {
            throw new DomainException('Current streak days cannot be negative');
        }

        if ($this->longestDays < 0) {
            throw new DomainException('Longest streak days cannot be negative');
        }

        if ($this->totalActiveDays < 0) {
            throw new DomainException('Total active days cannot be negative');
        }

        if ($this->currentDays > $this->longestDays) {
            throw new DomainException('Current streak cannot exceed longest streak');
        }

        if ($this->totalActiveDays < $this->longestDays) {
            throw new DomainException('Total active days cannot be less than longest streak');
        }
    }

    /**
     * Creates a zero streak (new user or reset).
     */
    public static function zero(): self
    {
        return new self(0, 0, 0);
    }

    /**
     * Reconstructs streak count from array data.
     *
     * @param array $data Array containing streak data.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            currentDays: (int) $data['current_days'],
            longestDays: (int) $data['longest_days'],
            totalActiveDays: (int) $data['total_active_days']
        );
    }

    /**
     * Increments the current streak.
     * Updates longest streak if new personal best.
     */
    public function increment(): self
    {
        $newCurrent = $this->currentDays + 1;
        $newLongest = max($this->longestDays, $newCurrent);
        $newTotal = $this->totalActiveDays + 1;

        return new self($newCurrent, $newLongest, $newTotal);
    }

    /**
     * Resets the current streak to 0 (streak break).
     * Preserves longest streak and total active days.
     */
    public function reset(): self
    {
        return new self(0, $this->longestDays, $this->totalActiveDays);
    }

    /**
     * Checks if the streak is currently active (non-zero).
     */
    public function isActive(): bool
    {
        return $this->currentDays > 0;
    }

    /**
     * Checks if this is a new personal best.
     */
    public function isNewPersonalBest(): bool
    {
        return $this->currentDays === $this->longestDays && $this->currentDays > 0;
    }

    /**
     * Gets the streak multiplier based on current streak.
     * Exponential growth: Day 1-3 → x1.0, Day 7 → x1.8, Day 14 → x2.7, Day 30+ → x4.0
     */
    public function getMultiplier(): float
    {
        return match (true) {
            $this->currentDays < 3 => 1.0,
            $this->currentDays < 7 => 1.0 + (($this->currentDays - 3) * 0.2), // 1.0-1.8
            $this->currentDays < 14 => 1.8 + (($this->currentDays - 7) * 0.13), // 1.8-2.7
            $this->currentDays < 30 => 2.7 + (($this->currentDays - 14) * 0.08), // 2.7-4.0
            $this->currentDays < 60 => 4.0 + (($this->currentDays - 30) * 0.017), // 4.0-4.5
            $this->currentDays < 90 => 4.5 + (($this->currentDays - 60) * 0.017), // 4.5-5.0
            $this->currentDays < 180 => 5.0 + (($this->currentDays - 90) * 0.011), // 5.0-6.0
            $this->currentDays < 365 => 6.0 + (($this->currentDays - 180) * 0.006), // 6.0-8.0
            default => 8.0,
        };
    }

    /**
     * Gets the hold days reduction based on streak.
     * Streak 7 days → -3 days, Streak 30 days → full unlock in 7 days instead of 15.
     *
     * @param int $baseHoldDays Base hold period (usually 15).
     */
    public function getHoldDaysReduction(int $baseHoldDays): int
    {
        return match (true) {
            $this->currentDays >= 365 => $baseHoldDays, // Instant unlock
            $this->currentDays >= 180 => $baseHoldDays - 1, // 1 day hold
            $this->currentDays >= 90 => $baseHoldDays - 3, // 3 day hold
            $this->currentDays >= 60 => $baseHoldDays - 5, // 5 day hold
            $this->currentDays >= 30 => max(0, $baseHoldDays - 7), // 7 day hold
            $this->currentDays >= 14 => 5,
            $this->currentDays >= 7 => 3,
            default => 0,
        };
    }

    /**
     * Gets the instant bonus reward for milestone.
     *
     * @return int Bonus amount in kopecks.
     */
    public function getMilestoneReward(): int
    {
        return match ($this->currentDays) {
            7 => 10000, // 100 ₽
            14 => 25000, // 250 ₽
            30 => 50000, // 500 ₽
            60 => 75000, // 750 ₽
            90 => 100000, // 1000 ₽
            180 => 200000, // 2000 ₽
            365 => 500000, // 5000 ₽
            default => 0,
        };
    }

    /**
     * Gets the milestone level name.
     */
    public function getMilestoneLevel(): ?string
    {
        return match ($this->currentDays) {
            3 => 'bronze',
            7 => 'silver',
            14 => 'gold',
            30 => 'platinum',
            60 => 'diamond',
            90 => 'premium',
            180 => 'legendary',
            365 => 'immortal',
            default => null,
        };
    }

    /**
     * Checks if the streak qualifies for a milestone.
     */
    public function isMilestone(): bool
    {
        return in_array($this->currentDays, [3, 7, 14, 30, 60, 90, 180, 365], true);
    }

    /**
     * Gets the next milestone and days to reach it.
     */
    public function getNextMilestone(): ?array
    {
        $milestones = [3, 7, 14, 30, 60, 90, 180, 365];
        foreach ($milestones as $milestone) {
            if ($this->currentDays < $milestone) {
                return [
                    'milestone' => $milestone,
                    'days_to_reach' => $milestone - $this->currentDays,
                    'multiplier' => $this->calculateMultiplierForMilestone($milestone),
                    'reward' => $this->calculateRewardForMilestone($milestone),
                    'level' => $this->getMilestoneLevelForDay($milestone),
                ];
            }
        }
        return null;
    }

    /**
     * Calculates multiplier for a specific milestone.
     */
    private function calculateMultiplierForMilestone(int $milestone): float
    {
        return match ($milestone) {
            3 => 1.0,
            7 => 1.8,
            14 => 2.7,
            30 => 4.0,
            60 => 4.5,
            90 => 5.0,
            180 => 6.0,
            365 => 8.0,
            default => 1.0,
        };
    }

    /**
     * Calculates reward for a specific milestone.
     */
    private function calculateRewardForMilestone(int $milestone): int
    {
        return match ($milestone) {
            7 => 10000,
            14 => 25000,
            30 => 50000,
            60 => 75000,
            90 => 100000,
            180 => 200000,
            365 => 500000,
            default => 0,
        };
    }

    /**
     * Gets milestone level for a specific day.
     */
    private function getMilestoneLevelForDay(int $day): ?string
    {
        return match ($day) {
            3 => 'bronze',
            7 => 'silver',
            14 => 'gold',
            30 => 'platinum',
            60 => 'diamond',
            90 => 'premium',
            180 => 'legendary',
            365 => 'immortal',
            default => null,
        };
    }

    /**
     * Gets the days until next multiplier increase.
     */
    public function getDaysUntilNextMultiplierIncrease(): int
    {
        return match (true) {
            $this->currentDays < 3 => 3 - $this->currentDays,
            $this->currentDays < 7 => 7 - $this->currentDays,
            $this->currentDays < 14 => 14 - $this->currentDays,
            $this->currentDays < 30 => 30 - $this->currentDays,
            $this->currentDays < 60 => 60 - $this->currentDays,
            $this->currentDays < 90 => 90 - $this->currentDays,
            $this->currentDays < 180 => 180 - $this->currentDays,
            $this->currentDays < 365 => 365 - $this->currentDays,
            default => 0,
        };
    }

    /**
     * Gets the streak engagement score (0-100).
     * Higher score indicates more engaged user.
     */
    public function getEngagementScore(): int
    {
        $score = min(100, ($this->currentDays / 30) * 100);
        return (int) $score;
    }

    /**
     * Gets the retention risk level.
     */
    public function getRetentionRisk(): string
    {
        return match (true) {
            $this->currentDays >= 30 => 'low',
            $this->currentDays >= 7 => 'medium',
            $this->currentDays >= 3 => 'high',
            default => 'critical',
        };
    }

    /**
     * Gets the current streak days.
     */
    public function getCurrentDays(): int
    {
        return $this->currentDays;
    }

    /**
     * Gets the longest streak days.
     */
    public function getLongestDays(): int
    {
        return $this->longestDays;
    }

    /**
     * Gets the total active days.
     */
    public function getTotalActiveDays(): int
    {
        return $this->totalActiveDays;
    }

    /**
     * Converts streak count to array for serialization.
     */
    public function toArray(): array
    {
        return [
            'current_days' => $this->currentDays,
            'longest_days' => $this->longestDays,
            'total_active_days' => $this->totalActiveDays,
            'multiplier' => $this->getMultiplier(),
            'is_active' => $this->isActive(),
            'is_new_personal_best' => $this->isNewPersonalBest(),
            'is_milestone' => $this->isMilestone(),
            'milestone_level' => $this->getMilestoneLevel(),
            'milestone_reward' => $this->getMilestoneReward(),
            'next_milestone' => $this->getNextMilestone(),
            'days_until_next_multiplier' => $this->getDaysUntilNextMultiplierIncrease(),
            'engagement_score' => $this->getEngagementScore(),
            'retention_risk' => $this->getRetentionRisk(),
        ];
    }
}
