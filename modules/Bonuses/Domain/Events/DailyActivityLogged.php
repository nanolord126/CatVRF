<?php

declare(strict_types=1);

namespace Modules\Bonuses\Domain\Events;

use DateTimeImmutable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event: DailyActivityLogged
 *
 * Dispatched when a user's daily activity is logged in the CatFloat system.
 * This event is the foundation for the gamified daily loop and streak mechanics.
 *
 * Key responsibilities:
 * - Calculate and update user's streak count
 * - Apply hold days reduction based on activity threshold
 * - Update streak multiplier for future bonus calculations
 * - Award loyalty points for daily activity
 * - Log audit trail for compliance (152-ФЗ, ФЗ-323)
 * - Send notification about streak progress
 * - Update analytics for daily active users (DAU)
 *
 * Activity threshold mechanics:
 * - User must login AND complete 3+ actions to meet threshold
 * - Actions: product view, AR try-on, review, purchase, cross-vertical visit, quest completion
 * - Each action has a score contribution (10-30 points)
 * - Threshold is 30 points (equivalent to 3 actions)
 * - Meeting threshold: -1 day hold on locked batches
 *
 * Activity scoring:
 * - Product view: 10 points
 * - AR try-on: 20 points
 * - Review: 25 points
 * - Purchase: 30 points
 * - Cross-vertical visit: 15 points
 * - Quest completion: 20 points
 * - Maximum daily score: 100 points
 *
 * Streak mechanics:
 * - Streak increments when user meets daily threshold
 * - Streak resets to 0 if user misses a day (no login or no threshold)
 * - Streak multiplier grows exponentially: 1.0x - 8.0x
 * - Multiplier applies to all bonus awards the next day
 *
 * Hold reduction mechanics:
 * - Daily threshold met: -1 day hold
 * - Streak 7 days: Additional -3 days hold
 * - Streak 30 days: Unlock in 7 days instead of 15
 * - Reductions stack and apply to oldest batch first
 *
 * Loyalty points:
 * - Daily login: 5 points
 * - Meeting threshold: +10 points
 * - Streak bonus: +5 points per streak day
 * - Points contribute to tier progression (Bronze → Silver → Gold → Platinum)
 *
 * Event flow:
 * 1. User performs actions → ActivityTrackerService tracks
 * 2. Daily activity logged → DailyActivityLogged event dispatched
 * 3. StreakCalculator listens → Updates streak count
 * 4. VestingService listens → Applies hold reduction
 * 5. LoyaltyCalculator listens → Awards loyalty points
 * 6. NotificationService listens → Sends streak notification
 * 7. AnalyticsService listens → Tracks DAU and retention
 *
 * Vertical activity tracking:
 * - Activity is tracked per vertical for cross-vertical bonuses
 * - Bonuses earned in Food give +25% if spent in Beauty within 48h
 * - Bonuses earned in Fashion give +20% if spent in Auto within 48h
 * - Cross-vertical multiplier applied at bonus consumption time
 *
 * @see Modules\Bonuses\Domain\Entities\DailyActivityLog
 * @see Modules\Bonuses\Domain\ValueObjects\ActivityScore
 * @see Modules\Bonuses\Domain\ValueObjects\StreakCount
 */
final class DailyActivityLogged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Unique identifier of the activity log record.
     */
    public readonly string $activityLogId;

    /**
     * User ID whose activity was logged.
     */
    public readonly string $userId;

    /**
     * Tenant ID for multi-tenancy support.
     */
    public readonly ?string $tenantId;

    /**
     * Date of activity (YYYY-MM-DD).
     */
    public readonly string $activityDate;

    /**
     * Whether user logged in on this day.
     */
    public readonly bool $hasLoggedIn;

    /**
     * Number of product views.
     */
    public readonly int $productViews;

    /**
     * Number of AR try-ons performed.
     */
    public readonly int $arTryOns;

    /**
     * Number of reviews submitted.
     */
    public readonly int $reviews;

    /**
     * Number of cross-vertical visits.
     */
    public readonly int $crossVerticalVisits;

    /**
     * Number of purchases made.
     */
    public readonly int $purchases;

    /**
     * Number of quests completed.
     */
    public readonly int $questsCompleted;

    /**
     * Total activity score (0-100).
     */
    public readonly int $activityScore;

    /**
     * Whether the user met the daily threshold (30+ points).
     */
    public readonly bool $meetsThreshold;

    /**
     * Current consecutive days of activity.
     */
    public readonly int $currentStreakDays;

    /**
     * Longest streak ever achieved.
     */
    public readonly int $longestStreakDays;

    /**
     * Total active days in user's history.
     */
    public readonly int $totalActiveDays;

    /**
     * Current streak multiplier.
     */
    public readonly float $streakMultiplier;

    /**
     * Hold days reduction applied based on activity.
     */
    public readonly int $holdDaysReduced;

    /**
     * Loyalty points awarded for this activity.
     */
    public readonly int $loyaltyPointsAwarded;

    /**
     * Activity breakdown by vertical.
     */
    public readonly array $verticalActivity;

    /**
     * Timestamp when activity was logged.
     */
    public readonly DateTimeImmutable $loggedAt;

    /**
     * Correlation ID for distributed tracing.
     */
    public readonly string $correlationId;

    /**
     * Additional metadata for analytics.
     */
    public readonly array $metadata;

    public function __construct(
        string $activityLogId,
        string $userId,
        string $activityDate,
        bool $hasLoggedIn,
        int $productViews,
        int $arTryOns,
        int $reviews,
        int $crossVerticalVisits,
        int $purchases,
        int $questsCompleted,
        int $activityScore,
        bool $meetsThreshold,
        int $currentStreakDays,
        int $longestStreakDays,
        int $totalActiveDays,
        float $streakMultiplier,
        int $holdDaysReduced,
        int $loyaltyPointsAwarded,
        array $verticalActivity,
        DateTimeImmutable $loggedAt,
        ?string $tenantId = null,
        string $correlationId,
        array $metadata = []
    ) {
        $this->activityLogId = $activityLogId;
        $this->userId = $userId;
        $this->tenantId = $tenantId;
        $this->activityDate = $activityDate;
        $this->hasLoggedIn = $hasLoggedIn;
        $this->productViews = $productViews;
        $this->arTryOns = $arTryOns;
        $this->reviews = $reviews;
        $this->crossVerticalVisits = $crossVerticalVisits;
        $this->purchases = $purchases;
        $this->questsCompleted = $questsCompleted;
        $this->activityScore = $activityScore;
        $this->meetsThreshold = $meetsThreshold;
        $this->currentStreakDays = $currentStreakDays;
        $this->longestStreakDays = $longestStreakDays;
        $this->totalActiveDays = $totalActiveDays;
        $this->streakMultiplier = $streakMultiplier;
        $this->holdDaysReduced = $holdDaysReduced;
        $this->loyaltyPointsAwarded = $loyaltyPointsAwarded;
        $this->verticalActivity = $verticalActivity;
        $this->loggedAt = $loggedAt;
        $this->correlationId = $correlationId;
        $this->metadata = $metadata;
    }

    /**
     * Gets the total number of actions performed.
     */
    public function getTotalActions(): int
    {
        return $this->productViews + $this->arTryOns + $this->reviews +
               $this->crossVerticalVisits + $this->purchases + $this->questsCompleted;
    }

    /**
     * Gets the most active vertical.
     */
    public function getMostActiveVertical(): ?string
    {
        if (empty($this->verticalActivity)) {
            return null;
        }

        $maxScore = 0;
        $mostActive = null;

        foreach ($this->verticalActivity as $vertical => $score) {
            if ($score > $maxScore) {
                $maxScore = $score;
                $mostActive = $vertical;
            }
        }

        return $mostActive;
    }

    /**
     * Checks if user visited multiple verticals.
     */
    public function isCrossVertical(): bool
    {
        return count($this->verticalActivity) > 1;
    }

    /**
     * Gets the number of verticals visited.
     */
    public function getVerticalsVisitedCount(): int
    {
        return count($this->verticalActivity);
    }

    /**
     * Checks if this is a streak milestone.
     */
    public function isStreakMilestone(): bool
    {
        return in_array($this->currentStreakDays, [3, 7, 14, 30, 60, 90, 180, 365], true);
    }

    /**
     * Gets the activity score as percentage of maximum.
     */
    public function getActivityScorePercentage(): float
    {
        return ($this->activityScore / 100) * 100;
    }

    /**
     * Converts event to array for serialization/logging.
     */
    public function toArray(): array
    {
        return [
            'activity_log_id' => $this->activityLogId,
            'user_id' => $this->userId,
            'tenant_id' => $this->tenantId,
            'activity_date' => $this->activityDate,
            'has_logged_in' => $this->hasLoggedIn,
            'product_views' => $this->productViews,
            'ar_try_ons' => $this->arTryOns,
            'reviews' => $this->reviews,
            'cross_vertical_visits' => $this->crossVerticalVisits,
            'purchases' => $this->purchases,
            'quests_completed' => $this->questsCompleted,
            'total_actions' => $this->getTotalActions(),
            'activity_score' => $this->activityScore,
            'activity_score_percentage' => $this->getActivityScorePercentage(),
            'meets_threshold' => $this->meetsThreshold,
            'current_streak_days' => $this->currentStreakDays,
            'longest_streak_days' => $this->longestStreakDays,
            'total_active_days' => $this->totalActiveDays,
            'streak_multiplier' => $this->streakMultiplier,
            'hold_days_reduced' => $this->holdDaysReduced,
            'loyalty_points_awarded' => $this->loyaltyPointsAwarded,
            'vertical_activity' => $this->verticalActivity,
            'most_active_vertical' => $this->getMostActiveVertical(),
            'is_cross_vertical' => $this->isCrossVertical(),
            'verticals_visited_count' => $this->getVerticalsVisitedCount(),
            'is_streak_milestone' => $this->isStreakMilestone(),
            'logged_at' => $this->loggedAt->format('Y-m-d H:i:s'),
            'correlation_id' => $this->correlationId,
            'metadata' => $this->metadata,
        ];
    }
}
