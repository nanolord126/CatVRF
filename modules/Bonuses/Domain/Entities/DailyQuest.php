<?php

declare(strict_types=1);

namespace Modules\Bonuses\Domain\Entities;

use DateTimeImmutable;
use DomainException;
use Modules\Bonuses\Domain\Enums\QuestStatus;
use Modules\Bonuses\Domain\Enums\QuestType;
use Ramsey\Uuid\Uuid;

/**
 * Entity: DailyQuest
 *
 * Represents a daily quest in the CatFloat gamified daily loop.
 * Quests drive specific user behaviors and reward engagement.
 *
 * Quest mechanics:
 * - Each user has 3-5 daily quests available
 * - Quests reset at midnight
 * - Quests have specific requirements and rewards
 * - Completion status tracked per user
 *
 * Quest types and requirements:
 * - PRODUCT_VIEW: View 8 products in a vertical → +15% bonus multiplier tomorrow
 * - AR_TRY_ON: Complete AR try-on in 2 verticals → -2 days hold
 * - REVIEW: Submit review with photo → +50 instant bonuses (with hold)
 * - CROSS_VERTICAL: Visit 3 different verticals → -2 days hold + 25% cross-vertical bonus
 * - PURCHASE: Complete purchase → -3 days hold + loyalty points
 * - LOGIN: Daily login → Required for streak, no direct reward
 * - SHARE: Share product → +20 bonuses + 10% multiplier
 * - REFERRAL: Successful referral → +500 bonuses + 5 days hold reduction
 *
 * Reward mechanics:
 * - Instant bonuses: Credited immediately with vesting curve
 * - Hold reduction: Applied to oldest locked batch
 * - Multiplier: Applies to bonuses earned the next day
 * - Loyalty points: Awarded for quest completion
 *
 * Sponsored quests:
 * - Brands pay for placement in daily quests
 * - Quest completion tracked for sponsor ROI
 * - Sponsor-specific rewards may override defaults
 * - Sponsor revenue tracked separately from main float revenue
 *
 * Quest difficulty scaling:
 * - Easy quests: Login, 3 product views
 * - Medium quests: 1 purchase, 1 review, 2 vertical visits
 * - Hard quests: 5 purchases, 3 reviews, referral
 * - Quest difficulty adapts to user engagement level
 *
 * Quest generation:
 * - Quests generated daily at midnight
 * - Quests personalized based on user behavior
 * - Quests balanced across verticals
 * - Sponsored quests prioritized when available
 *
 * Completion tracking:
 * - Progress tracked per quest requirement
 * - Quest marked complete when all requirements met
 * - Rewards awarded immediately on completion
 * - Quest marked claimed when rewards claimed
 *
 * Compliance:
 * - All quest completions logged with correlation ID
 * - Audit trail for sponsored quest performance
 * - PII anonymized in external logs (152-ФЗ)
 *
 * @see Modules\Bonuses\Domain\Enums\QuestType
 * @see Modules\Bonuses\Domain\Enums\QuestStatus
 */
final readonly class DailyQuest
{
    /**
     * Unique identifier for this quest.
     */
    public string $id;

    /**
     * Quest type.
     */
    public QuestType $type;

    /**
     * Quest title (displayed to user).
     */
    public string $title;

    /**
     * Quest description.
     */
    public string $description;

    /**
     * Quest requirements (JSON).
     */
    public array $requirements;

    /**
     * Instant bonus reward (kopecks).
     */
    public int $bonusReward;

    /**
     * Hold days reduction reward.
     */
    public int $holdReductionReward;

    /**
     * Bonus multiplier reward (as percentage, e.g., 15 for 15%).
     */
    public float $multiplierReward;

    /**
     * Loyalty points reward.
     */
    public int $loyaltyPointsReward;

    /**
     * Quest date (YYYY-MM-DD).
     */
    public string $questDate;

    /**
     * Vertical where quest applies (null for global).
     */
    public ?string $vertical;

    /**
     * Difficulty level (easy, medium, hard).
     */
    public string $difficulty;

    /**
     * Whether this is a sponsored quest.
     */
    public bool $isSponsored;

    /**
     * Sponsor name (if sponsored).
     */
    public ?string $sponsorName;

    /**
     * Sponsor reward override (kopecks).
     */
    public ?int $sponsorReward;

    /**
     * Quest priority (higher = more likely to be shown).
     */
    public int $priority;

    /**
     * Timestamp when quest was created.
     */
    public DateTimeImmutable $createdAt;

    /**
     * Timestamp when quest expires.
     */
    public DateTimeImmutable $expiresAt;

    /**
     * Correlation ID for distributed tracing.
     */
    public string $correlationId;

    /**
     * Additional metadata.
     */
    public array $metadata;

    /**
     * Creates a new daily quest.
     */
    public static function create(
        QuestType $type,
        string $title,
        string $description,
        array $requirements,
        string $questDate,
        ?string $vertical = null,
        string $difficulty = 'medium',
        bool $isSponsored = false,
        ?string $sponsorName = null,
        ?int $sponsorReward = null,
        int $priority = 0,
        string $correlationId = null,
        array $metadata = []
    ): self {
        $id = Uuid::uuid4()->toString();
        $now = new DateTimeImmutable();
        $correlationId = $correlationId ?? Uuid::uuid4()->toString();

        // Set default rewards based on quest type
        $bonusReward = match ($type) {
            QuestType::LOGIN => 0,
            QuestType::PRODUCT_VIEW => 0,
            QuestType::AR_TRY_ON => 0,
            QuestType::REVIEW => 5000, // 50 ₽
            QuestType::CROSS_VERTICAL => 0,
            QuestType::PURCHASE => 0,
            QuestType::SHARE => 2000, // 20 ₽
            QuestType::REFERRAL => 50000, // 500 ₽
        };
        $holdReduction = match ($type) {
            QuestType::LOGIN => 0,
            QuestType::PRODUCT_VIEW => 0,
            QuestType::AR_TRY_ON => 2,
            QuestType::REVIEW => 0,
            QuestType::CROSS_VERTICAL => 2,
            QuestType::PURCHASE => 3,
            QuestType::SHARE => 0,
            QuestType::REFERRAL => 5,
        };
        $multiplier = match ($type) {
            QuestType::LOGIN => 0,
            QuestType::PRODUCT_VIEW => 15,
            QuestType::AR_TRY_ON => 0,
            QuestType::REVIEW => 0,
            QuestType::CROSS_VERTICAL => 25,
            QuestType::PURCHASE => 0,
            QuestType::SHARE => 10,
            QuestType::REFERRAL => 0,
        };
        $loyaltyPoints = match ($type) {
            QuestType::LOGIN => 5,
            QuestType::PRODUCT_VIEW => 10,
            QuestType::AR_TRY_ON => 20,
            QuestType::REVIEW => 25,
            QuestType::CROSS_VERTICAL => 15,
            QuestType::PURCHASE => 30,
            QuestType::SHARE => 10,
            QuestType::REFERRAL => 50,
        };

        // Override with sponsor reward if sponsored
        if ($isSponsored && $sponsorReward !== null) {
            $bonusReward = $sponsorReward;
        }

        $expiresAt = $now->modify('+1 day')->setTime(23, 59, 59);

        return new self(
            id: $id,
            type: $type,
            title: $title,
            description: $description,
            requirements: $requirements,
            bonusReward: $bonusReward,
            holdReductionReward: $holdReduction,
            multiplierReward: $multiplier,
            loyaltyPointsReward: $loyaltyPoints,
            questDate: $questDate,
            vertical: $vertical,
            difficulty: $difficulty,
            isSponsored: $isSponsored,
            sponsorName: $sponsorName,
            sponsorReward: $sponsorReward,
            priority: $priority,
            createdAt: $now,
            expiresAt: $expiresAt,
            correlationId: $correlationId,
            metadata: $metadata
        );
    }

    /**
     * Private constructor.
     */
    private function __construct(
        string $id,
        QuestType $type,
        string $title,
        string $description,
        array $requirements,
        int $bonusReward,
        int $holdReductionReward,
        float $multiplierReward,
        int $loyaltyPointsReward,
        string $questDate,
        ?string $vertical,
        string $difficulty,
        bool $isSponsored,
        ?string $sponsorName,
        ?int $sponsorReward,
        int $priority,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $expiresAt,
        string $correlationId,
        array $metadata
    ) {
        $this->id = $id;
        $this->type = $type;
        $this->title = $title;
        $this->description = $description;
        $this->requirements = $requirements;
        $this->bonusReward = $bonusReward;
        $this->holdReductionReward = $holdReductionReward;
        $this->multiplierReward = $multiplierReward;
        $this->loyaltyPointsReward = $loyaltyPointsReward;
        $this->questDate = $questDate;
        $this->vertical = $vertical;
        $this->difficulty = $difficulty;
        $this->isSponsored = $isSponsored;
        $this->sponsorName = $sponsorName;
        $this->sponsorReward = $sponsorReward;
        $this->priority = $priority;
        $this->createdAt = $createdAt;
        $this->expiresAt = $expiresAt;
        $this->correlationId = $correlationId;
        $this->metadata = $metadata;
    }

    /**
     * Reconstructs quest from array data.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            type: QuestType::fromString($data['type']),
            title: $data['title'],
            description: $data['description'],
            requirements: $data['requirements'],
            bonusReward: (int) $data['bonus_reward'],
            holdReductionReward: (int) $data['hold_reduction_reward'],
            multiplierReward: (float) $data['multiplier_reward'],
            loyaltyPointsReward: (int) $data['loyalty_points_reward'],
            questDate: $data['quest_date'],
            vertical: $data['vertical'] ?? null,
            difficulty: $data['difficulty'],
            isSponsored: (bool) $data['is_sponsored'],
            sponsorName: $data['sponsor_name'] ?? null,
            sponsorReward: $data['sponsor_reward'] ?? null,
            priority: (int) $data['priority'],
            createdAt: new DateTimeImmutable($data['created_at']),
            expiresAt: new DateTimeImmutable($data['expires_at']),
            correlationId: $data['correlation_id'],
            metadata: $data['metadata'] ?? []
        );
    }

    /**
     * Checks if quest has expired.
     */
    public function isExpired(DateTimeImmutable $now): bool
    {
        return $now > $this->expiresAt;
    }

    /**
     * Checks if quest is active.
     */
    public function isActive(DateTimeImmutable $now): bool
    {
        return !$this->isExpired($now);
    }

    /**
     * Gets the total reward value.
     */
    public function getTotalRewardValue(): int
    {
        return $this->bonusReward + $this->loyaltyPointsReward;
    }

    /**
     * Checks if quest has bonus reward.
     */
    public function hasBonusReward(): bool
    {
        return $this->bonusReward > 0;
    }

    /**
     * Checks if quest has hold reduction reward.
     */
    public function hasHoldReduction(): bool
    {
        return $this->holdReductionReward > 0;
    }

    /**
     * Checks if quest has multiplier reward.
     */
    public function hasMultiplierReward(): bool
    {
        return $this->multiplierReward > 0;
    }

    /**
     * Checks if quest is sponsored.
     */
    public function isSponsoredQuest(): bool
    {
        return $this->isSponsored;
    }

    /**
     * Gets the reward summary.
     */
    public function getRewardSummary(): array
    {
        return [
            'bonus_reward' => $this->bonusReward,
            'hold_reduction' => $this->holdReductionReward,
            'multiplier_reward' => $this->multiplierReward,
            'loyalty_points' => $this->loyaltyPointsReward,
            'total_value' => $this->getTotalRewardValue(),
            'has_bonus' => $this->hasBonusReward(),
            'has_hold_reduction' => $this->hasHoldReduction(),
            'has_multiplier' => $this->hasMultiplierReward(),
        ];
    }

    /**
     * Converts quest to array for serialization.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'title' => $this->title,
            'description' => $this->description,
            'requirements' => $this->requirements,
            'bonus_reward' => $this->bonusReward,
            'hold_reduction_reward' => $this->holdReductionReward,
            'multiplier_reward' => $this->multiplierReward,
            'loyalty_points_reward' => $this->loyaltyPointsReward,
            'quest_date' => $this->questDate,
            'vertical' => $this->vertical,
            'difficulty' => $this->difficulty,
            'is_sponsored' => $this->isSponsored,
            'sponsor_name' => $this->sponsorName,
            'sponsor_reward' => $this->sponsorReward,
            'priority' => $this->priority,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'expires_at' => $this->expiresAt->format('Y-m-d H:i:s'),
            'correlation_id' => $this->correlationId,
            'metadata' => $this->metadata,
            'reward_summary' => $this->getRewardSummary(),
        ];
    }
}
