<?php

declare(strict_types=1);

namespace Modules\Bonuses\Domain\Events;

use DateTimeImmutable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event: StreakAchieved
 *
 * Dispatched when a user achieves or updates their daily activity streak in CatFloat.
 * Streaks are a core retention mechanic that drives daily user engagement through gamification.
 *
 * Key responsibilities:
 * - Update user's streak multiplier for future bonus calculations
 * - Apply bonus hold reduction based on streak milestone
 * - Send milestone notifications for significant achievements
 * - Log audit trail for compliance (152-ФЗ, ФЗ-323)
 * - Update analytics for retention metrics
 * - Trigger special rewards for milestone streaks
 *
 * Streak mechanics:
 * - Streak increments when user meets daily activity threshold (3+ actions + login)
 * - Streak resets to 0 if user misses a day
 * - Multiplier grows exponentially with streak length
 * - Hold reduction increases at specific milestones
 *
 * Streak milestones and rewards:
 * - Day 1-3: ×1.0 multiplier, no hold reduction
 * - Day 7: ×1.8 multiplier, -3 days hold reduction
 * - Day 14: ×2.7 multiplier, -5 days hold reduction
 * - Day 30: ×4.0 multiplier, unlock in 7 days instead of 15
 * - Day 60: ×4.5 multiplier, unlock in 5 days instead of 15
 * - Day 90: ×5.0 multiplier, unlock in 3 days instead of 15
 * - Day 180: ×6.0 multiplier, instant unlock (1 day)
 * - Day 365: Legendary status, ×8.0 multiplier, all bonuses instant
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
 * Milestone rewards:
 * - Day 7: +100 instant bonuses
 * - Day 14: +250 instant bonuses
 * - Day 30: +500 instant bonuses + special badge
 * - Day 60: +750 instant bonuses + exclusive quests
 * - Day 90: +1000 instant bonuses + premium badge
 * - Day 180: +2000 instant bonuses + legendary badge
 * - Day 365: +5000 instant bonuses + legendary status
 *
 * Event flow:
 * 1. DailyActivityLogged event → StreakCalculator checks streak
 * 2. Streak updated → StreakAchieved event dispatched
 * 3. VestingService listens → Applies hold reduction to batches
 * 4. LoyaltyCalculator listens → Updates streak multiplier
 * 5. RewardService listens → Awards milestone rewards
 * 6. NotificationService listens → Sends milestone notification
 * 7. AnalyticsService listens → Tracks retention metrics
 *
 * @see Modules\Bonuses\Domain\ValueObjects\StreakCount
 * @see Modules\Bonuses\Application\Services\VestingService
 */
final class StreakAchieved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * User ID who achieved the streak.
     */
    public readonly string $userId;

    /**
     * Tenant ID for multi-tenancy support.
     */
    public readonly ?string $tenantId;

    /**
     * Current consecutive days of activity.
     */
    public readonly int $currentStreakDays;

    /**
     * Longest streak ever achieved by this user.
     */
    public readonly int $longestStreakDays;

    /**
     * Total active days in user's history.
     */
    public readonly int $totalActiveDays;

    /**
     * Streak multiplier based on current streak.
     * Grows exponentially: 1.0x - 8.0x
     */
    public readonly float $streakMultiplier;

    /**
     * Hold days reduction based on streak milestone.
     */
    public readonly int $holdDaysReduction;

    /**
     * Whether this is a milestone streak (7, 14, 30, 60, 90, 180, 365).
     */
    public readonly bool $isMilestone;

    /**
     * Milestone level if this is a milestone.
     */
    public readonly ?string $milestoneLevel;

    /**
     * Instant bonus reward for milestone (in kopecks).
     */
    public readonly int $milestoneReward;

    /**
     * Previous streak before this update.
     */
    public readonly int $previousStreakDays;

    /**
     * Whether the streak was reset (user missed a day).
     */
    public readonly bool $wasReset;

    /**
     * Timestamp when the streak was achieved.
     */
    public readonly DateTimeImmutable $achievedAt;

    /**
     * Correlation ID for distributed tracing.
     */
    public readonly string $correlationId;

    /**
     * Additional metadata for analytics.
     */
    public readonly array $metadata;

    public function __construct(
        string $userId,
        int $currentStreakDays,
        int $longestStreakDays,
        int $totalActiveDays,
        float $streakMultiplier,
        int $holdDaysReduction,
        bool $isMilestone,
        ?string $milestoneLevel,
        int $milestoneReward,
        int $previousStreakDays,
        bool $wasReset,
        DateTimeImmutable $achievedAt,
        ?string $tenantId = null,
        string $correlationId,
        array $metadata = []
    ) {
        $this->userId = $userId;
        $this->tenantId = $tenantId;
        $this->currentStreakDays = $currentStreakDays;
        $this->longestStreakDays = $longestStreakDays;
        $this->totalActiveDays = $totalActiveDays;
        $this->streakMultiplier = $streakMultiplier;
        $this->holdDaysReduction = $holdDaysReduction;
        $this->isMilestone = $isMilestone;
        $this->milestoneLevel = $milestoneLevel;
        $this->milestoneReward = $milestoneReward;
        $this->previousStreakDays = $previousStreakDays;
        $this->wasReset = $wasReset;
        $this->achievedAt = $achievedAt;
        $this->correlationId = $correlationId;
        $this->metadata = $metadata;
    }

    /**
     * Checks if this is a new personal best.
     */
    public function isNewPersonalBest(): bool
    {
        return $this->currentStreakDays === $this->longestStreakDays && !$this->wasReset;
    }

    /**
     * Gets the streak increase from previous.
     */
    public function getStreakIncrease(): int
    {
        return $this->wasReset ? $this->currentStreakDays : ($this->currentStreakDays - $this->previousStreakDays);
    }

    /**
     * Gets the multiplier increase from previous.
     */
    public function getMultiplierIncrease(): float
    {
        $previousMultiplier = $this->calculatePreviousMultiplier();
        return $this->streakMultiplier - $previousMultiplier;
    }

    /**
     * Calculates the previous multiplier based on previous streak.
     */
    private function calculatePreviousMultiplier(): float
    {
        $previousStreak = $this->wasReset ? 0 : $this->previousStreakDays;
        return match (true) {
            $previousStreak < 3 => 1.0,
            $previousStreak < 7 => 1.0 + (($previousStreak - 3) * 0.2),
            $previousStreak < 14 => 1.8 + (($previousStreak - 7) * 0.13),
            $previousStreak < 30 => 2.7 + (($previousStreak - 14) * 0.08),
            $previousStreak < 60 => 4.0 + (($previousStreak - 30) * 0.017),
            $previousStreak < 90 => 4.5 + (($previousStreak - 60) * 0.017),
            $previousStreak < 180 => 5.0 + (($previousStreak - 90) * 0.011),
            $previousStreak < 365 => 6.0 + (($previousStreak - 180) * 0.006),
            default => 8.0,
        };
    }

    /**
     * Gets the next milestone and days to reach it.
     */
    public function getNextMilestone(): ?array
    {
        $milestones = [7, 14, 30, 60, 90, 180, 365];
        foreach ($milestones as $milestone) {
            if ($this->currentStreakDays < $milestone) {
                return [
                    'milestone' => $milestone,
                    'days_to_reach' => $milestone - $this->currentStreakDays,
                    'multiplier' => $this->calculateMultiplierForMilestone($milestone),
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
     * Converts event to array for serialization/logging.
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'tenant_id' => $this->tenantId,
            'current_streak_days' => $this->currentStreakDays,
            'longest_streak_days' => $this->longestStreakDays,
            'total_active_days' => $this->totalActiveDays,
            'streak_multiplier' => $this->streakMultiplier,
            'hold_days_reduction' => $this->holdDaysReduction,
            'is_milestone' => $this->isMilestone,
            'milestone_level' => $this->milestoneLevel,
            'milestone_reward' => $this->milestoneReward,
            'previous_streak_days' => $this->previousStreakDays,
            'was_reset' => $this->wasReset,
            'streak_increase' => $this->getStreakIncrease(),
            'multiplier_increase' => $this->getMultiplierIncrease(),
            'is_new_personal_best' => $this->isNewPersonalBest(),
            'next_milestone' => $this->getNextMilestone(),
            'achieved_at' => $this->achievedAt->format('Y-m-d H:i:s'),
            'correlation_id' => $this->correlationId,
            'metadata' => $this->metadata,
        ];
    }
}
