<?php

declare(strict_types=1);

namespace Modules\Bonuses\Domain\Events;

use DateTimeImmutable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event: QuestCompleted
 *
 * Dispatched when a user completes a daily quest in the CatFloat gamified daily loop.
 * This event triggers reward distribution, hold period reduction, and streak updates.
 *
 * Key responsibilities:
 * - Distribute instant bonus rewards to user's bonus wallet
 * - Apply hold days reduction to user's locked bonus batches
 * - Update streak multiplier for future bonus calculations
 * - Award loyalty points for quest completion
 * - Log audit trail for compliance (152-ФЗ, ФЗ-323)
 * - Send notification to user about quest completion and rewards
 * - Update daily activity log with quest progress
 * - Track sponsored quest performance for brand partners
 *
 * Quest types and rewards:
 * - PRODUCT_VIEW: View 8 products in a vertical → +15% bonus multiplier tomorrow
 * - AR_TRY_ON: Complete AR try-on in 2 verticals → -2 days hold
 * - REVIEW: Submit review with photo → +50 instant bonuses (with hold)
 * - CROSS_VERTICAL: Visit 3 different verticals → -2 days hold + 25% cross-vertical bonus
 * - PURCHASE: Complete purchase → -3 days hold + loyalty points
 * - LOGIN: Daily login → Required for streak, no direct reward
 * - SHARE: Share product → +20 bonuses + 10% multiplier
 * - REFERRAL: Successful referral → +500 bonuses + 5 days hold reduction
 *
 * Hold reduction mechanics:
 * - Hold reduction is applied to the oldest locked batch first
 * - Reduction cannot make hold period less than 1 day
 * - Multiple quest completions stack hold reductions
 * - Streak multipliers also contribute to hold reduction
 *
 * Bonus multiplier mechanics:
 * - Multipliers apply to bonuses earned the next day
 * - Multipliers stack: base × streak × quest × tier
 * - Maximum possible multiplier: 1.0 × 4.0 (streak) × 1.5 (quest) × 1.2 (tier) = 7.2x
 * - Multipliers reset daily if not maintained
 *
 * Sponsored quests:
 * - Brands pay for placement in daily quests
 * - Quest completion tracked for sponsor ROI
 * - Sponsor-specific rewards may override defaults
 * - Sponsor revenue tracked separately from main float revenue
 *
 * Event flow:
 * 1. User completes quest action → QuestProgressService validates
 * 2. QuestCompleted event dispatched → Multiple listeners react
 * 3. WalletIntegrationService → Awards instant bonus
 * 4. VestingService → Applies hold reduction
 * 5. LoyaltyCalculator → Updates streak and multiplier
 * 6. NotificationService → Sends completion notification
 * 7. AnalyticsService → Tracks quest completion metrics
 * 8. SponsorTrackingService → Logs sponsored quest performance
 *
 * @see Modules\Bonuses\Application\Services\QuestEngineService
 * @see Modules\Bonuses\Application\Services\VestingService
 * @see Modules\Bonuses\Domain\ValueObjects\ActivityScore
 */
final class QuestCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Unique identifier of the user's quest progress record.
     */
    public readonly string $userQuestProgressId;

    /**
     * User ID who completed the quest.
     */
    public readonly string $userId;

    /**
     * Tenant ID for multi-tenancy support.
     */
    public readonly ?string $tenantId;

    /**
     * Quest ID that was completed.
     */
    public readonly string $questId;

    /**
     * Quest key for identification (e.g., 'view_8_products_beauty').
     */
    public readonly string $questKey;

    /**
     * Quest type (product_view, ar_tryon, review, etc.).
     */
    public readonly string $questType;

    /**
     * Vertical where the quest was completed (if applicable).
     */
    public readonly ?string $vertical;

    /**
     * Instant bonus reward awarded (in kopecks).
     * This bonus is subject to the standard 15-day hold period.
     */
    public readonly int $bonusReward;

    /**
     * Hold days reduction applied to locked batches.
     * Applied to the oldest locked batch first.
     */
    public readonly int $holdDaysReduction;

    /**
     * Bonus multiplier for tomorrow's bonus awards.
     * Multiplies the base bonus amount by this factor.
     */
    public readonly float $bonusMultiplier;

    /**
     * Loyalty points awarded for quest completion.
     * Contributes to user's loyalty tier progression.
     */
    public readonly int $loyaltyPoints;

    /**
     * Current streak days at completion time.
     */
    public readonly int $currentStreakDays;

    /**
     * Streak multiplier at completion time.
     */
    public readonly float $streakMultiplier;

    /**
     * Whether this quest is sponsored by a brand.
     */
    public readonly bool $isSponsored;

    /**
     * Sponsor ID if this is a sponsored quest.
     */
    public readonly ?string $sponsorId;

    /**
     * Timestamp when the quest was completed.
     */
    public readonly DateTimeImmutable $completedAt;

    /**
     * Correlation ID for distributed tracing.
     */
    public readonly string $correlationId;

    /**
     * Additional metadata for analytics and tracking.
     */
    public readonly array $metadata;

    public function __construct(
        string $userQuestProgressId,
        string $userId,
        string $questId,
        string $questKey,
        string $questType,
        ?string $vertical,
        int $bonusReward,
        int $holdDaysReduction,
        float $bonusMultiplier,
        int $loyaltyPoints,
        int $currentStreakDays,
        float $streakMultiplier,
        bool $isSponsored,
        ?string $sponsorId,
        DateTimeImmutable $completedAt,
        ?string $tenantId = null,
        string $correlationId,
        array $metadata = []
    ) {
        $this->userQuestProgressId = $userQuestProgressId;
        $this->userId = $userId;
        $this->tenantId = $tenantId;
        $this->questId = $questId;
        $this->questKey = $questKey;
        $this->questType = $questType;
        $this->vertical = $vertical;
        $this->bonusReward = $bonusReward;
        $this->holdDaysReduction = $holdDaysReduction;
        $this->bonusMultiplier = $bonusMultiplier;
        $this->loyaltyPoints = $loyaltyPoints;
        $this->currentStreakDays = $currentStreakDays;
        $this->streakMultiplier = $streakMultiplier;
        $this->isSponsored = $isSponsored;
        $this->sponsorId = $sponsorId;
        $this->completedAt = $completedAt;
        $this->correlationId = $correlationId;
        $this->metadata = $metadata;
    }

    /**
     * Gets the effective multiplier including streak.
     */
    public function getEffectiveMultiplier(): float
    {
        return $this->bonusMultiplier * $this->streakMultiplier;
    }

    /**
     * Checks if this quest contributes to hold reduction.
     */
    public function hasHoldReduction(): bool
    {
        return $this->holdDaysReduction > 0;
    }

    /**
     * Checks if this quest contributes to bonus multiplier.
     */
    public function hasBonusMultiplier(): bool
    {
        return $this->bonusMultiplier > 1.0;
    }

    /**
     * Gets the total reward value (bonus + loyalty points equivalent).
     */
    public function getTotalRewardValue(): int
    {
        // Loyalty points typically worth 1 kopeck each
        return $this->bonusReward + $this->loyaltyPoints;
    }

    /**
     * Converts event to array for serialization/logging.
     */
    public function toArray(): array
    {
        return [
            'user_quest_progress_id' => $this->userQuestProgressId,
            'user_id' => $this->userId,
            'tenant_id' => $this->tenantId,
            'quest_id' => $this->questId,
            'quest_key' => $this->questKey,
            'quest_type' => $this->questType,
            'vertical' => $this->vertical,
            'bonus_reward' => $this->bonusReward,
            'hold_days_reduction' => $this->holdDaysReduction,
            'bonus_multiplier' => $this->bonusMultiplier,
            'loyalty_points' => $this->loyaltyPoints,
            'current_streak_days' => $this->currentStreakDays,
            'streak_multiplier' => $this->streakMultiplier,
            'effective_multiplier' => $this->getEffectiveMultiplier(),
            'is_sponsored' => $this->isSponsored,
            'sponsor_id' => $this->sponsorId,
            'completed_at' => $this->completedAt->format('Y-m-d H:i:s'),
            'correlation_id' => $this->correlationId,
            'metadata' => $this->metadata,
        ];
    }
}
