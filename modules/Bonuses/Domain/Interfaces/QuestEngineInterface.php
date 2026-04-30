<?php

declare(strict_types=1);

namespace Modules\Bonuses\Domain\Interfaces;

use Modules\Bonuses\Domain\Entities\DailyQuest;
use Modules\Bonuses\Domain\Enums\QuestType;
use Modules\Bonuses\Domain\ValueObjects\ActivityScore;

/**
 * Interface: QuestEngineInterface
 *
 * Defines the contract for the quest engine that generates, validates, and tracks daily quests
 * in the CatFloat gamified daily loop.
 *
 * Quest engine responsibilities:
 * - Generate daily quests for users based on behavior and preferences
 * - Validate quest completion based on user activity
 * - Track quest progress and status
 * - Award quest rewards upon completion
 * - Manage sponsored quest placement
 * - Reset quests daily at midnight
 *
 * Quest generation logic:
 * - 3-5 quests generated per user daily
 * - Quests personalized based on user engagement level
 * - Quests balanced across verticals (beauty, food, fashion, healthcare)
 * - Sponsored quests prioritized when available
 * - Quest difficulty adapts to user's engagement history
 *
 * Quest types:
 * - PRODUCT_VIEW: View 8 products in a vertical → +15% bonus multiplier
 * - AR_TRY_ON: Complete AR try-on in 2 verticals → -2 days hold
 * - REVIEW: Submit review with photo → +50 instant bonuses
 * - CROSS_VERTICAL: Visit 3 different verticals → -2 days hold + 25% bonus
 * - PURCHASE: Complete purchase → -3 days hold + loyalty points
 * - LOGIN: Daily login → Required for streak
 * - SHARE: Share product → +20 bonuses + 10% multiplier
 * - REFERRAL: Successful referral → +500 bonuses + 5 days hold reduction
 *
 * Quest validation:
 * - Progress tracked per quest requirement
 * - Quest marked complete when all requirements met
 * - Rewards awarded immediately on completion
 * - Quest marked claimed when rewards claimed by user
 * - Quest expires at midnight if not completed
 *
 * Sponsored quests:
 * - Brands pay for placement in daily quest slots
 * - Sponsor-specific rewards may override defaults
 * - Quest completion tracked for sponsor ROI
 * - Sponsor revenue tracked separately from main float revenue
 * - Sponsor quests have higher priority in generation
 *
 * Difficulty scaling:
 * - Easy: Login, 3 product views (high completion rate)
 * - Medium: 1 purchase, 1 review, 2 vertical visits (balanced)
 * - Hard: 5 purchases, 3 reviews, referral (high reward)
 * - Difficulty adapts based on user's 7-day completion rate
 *
 * Implementation requirements:
 * - Must be idempotent (same inputs = same quest set)
 * - Must handle user preferences and vertical interests
 * - Must validate all quest requirements before marking complete
 * - Must throw DomainException for invalid operations
 * - Must log all quest events with correlation ID
 *
 * Performance considerations:
 * - Quest generation should be O(n) where n = number of quests
 * - Use caching for quest templates
 * - Batch quest validation for efficiency
 * - Use Redis for real-time progress tracking
 *
 * Compliance:
 * - All quest events logged with correlation ID
 * - Audit trail for sponsored quest performance
 * - PII anonymized in external logs (152-ФЗ)
 *
 * @see Modules\Bonuses\Domain\Entities\DailyQuest
 * @see Modules\Bonuses\Domain\Enums\QuestType
 * @see Modules\Bonuses\Domain\ValueObjects\ActivityScore
 */
interface QuestEngineInterface
{
    /**
     * Generates daily quests for a user.
     *
     * @param string $userId User ID.
     * @param string $questDate Quest date (YYYY-MM-DD).
     * @param int $count Number of quests to generate (default 3).
     * @return array Array of DailyQuest entities.
     */
    public function generateDailyQuests(string $userId, string $questDate, int $count = 3): array;

    /**
     * Validates quest completion based on user activity.
     *
     * @param DailyQuest $quest The quest to validate.
     * @param ActivityScore $activityScore User's activity score.
     * @return bool True if quest is complete.
     */
    public function validateQuestCompletion(DailyQuest $quest, ActivityScore $activityScore): bool;

    /**
     * Calculates quest progress percentage.
     *
     * @param DailyQuest $quest The quest.
     * @param ActivityScore $activityScore User's activity score.
     * @return float Progress percentage (0-100).
     */
    public function calculateQuestProgress(DailyQuest $quest, ActivityScore $activityScore): float;

    /**
     * Awards quest rewards to the user.
     *
     * @param DailyQuest $quest The completed quest.
     * @param string $userId User ID.
     * @return array Summary of awarded rewards.
     */
    public function awardQuestRewards(DailyQuest $quest, string $userId): array;

    /**
     * Resets daily quests for a user.
     *
     * @param string $userId User ID.
     * @param string $questDate Quest date to reset (YYYY-MM-DD).
     * @return int Number of quests reset.
     */
    public function resetDailyQuests(string $userId, string $questDate): int;

    /**
     * Gets available quest templates for generation.
     *
     * @param string $vertical Vertical filter (null for all).
     * @param string $difficulty Difficulty filter (null for all).
     * @return array Array of quest templates.
     */
    public function getQuestTemplates(?string $vertical = null, ?string $difficulty = null): array;

    /**
     * Creates a sponsored quest for a brand.
     *
     * @param QuestType $type Quest type.
     * @param string $sponsorName Sponsor brand name.
     * @param int $sponsorReward Sponsor-specific reward (kopecks).
     * @param string $vertical Target vertical.
     * @param string $questDate Quest date (YYYY-MM-DD).
     * @return DailyQuest The sponsored quest.
     */
    public function createSponsoredQuest(
        QuestType $type,
        string $sponsorName,
        int $sponsorReward,
        string $vertical,
        string $questDate
    ): DailyQuest;

    /**
     * Gets user's quest completion statistics.
     *
     * @param string $userId User ID.
     * @param int $days Number of days to look back (default 30).
     * @return array Statistics including completion rate, favorite types, etc.
     */
    public function getUserQuestStats(string $userId, int $days = 30): array;

    /**
     * Calculates quest difficulty based on user behavior.
     *
     * @param string $userId User ID.
     * @return string Difficulty level (easy, medium, hard).
     */
    public function calculateQuestDifficulty(string $userId): string;

    /**
     * Gets recommended quest types for a user.
     *
     * @param string $userId User ID.
     * @param int $count Number of recommendations (default 3).
     * @return array Array of QuestType enums.
     */
    public function getRecommendedQuestTypes(string $userId, int $count = 3): array;

    /**
     * Validates a quest configuration.
     *
     * @param DailyQuest $quest The quest to validate.
     * @return bool True if valid.
     * @throws DomainException If invalid.
     */
    public function validateQuestConfiguration(DailyQuest $quest): bool;
}
