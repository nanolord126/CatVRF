<?php

declare(strict_types=1);

namespace Modules\Bonuses\Application\Services;

use DomainException;
use Modules\Bonuses\Domain\Entities\DailyQuest;
use Modules\Bonuses\Domain\Enums\QuestStatus;
use Modules\Bonuses\Domain\Enums\QuestType;
use Modules\Bonuses\Domain\Interfaces\QuestEngineInterface;
use Modules\Bonuses\Domain\ValueObjects\ActivityScore;
use Modules\Bonuses\Infrastructure\Models\DailyQuestModel;
use Modules\Bonuses\Infrastructure\Models\UserQuestProgressModel;

/**
 * Service: QuestEngineService
 *
 * Application service for managing daily quests in the CatFloat gamified system.
 * Implements the QuestEngineInterface to provide quest generation and management.
 *
 * Core responsibilities:
 * - Generate daily quests for users based on behavior
 * - Validate quest completion based on user activity
 * - Track quest progress and status
 * - Award quest rewards upon completion
 * - Manage sponsored quest placement
 *
 * No facades - all dependencies injected via constructor.
 *
 * @see Modules\Bonuses\Domain\Entities\DailyQuest
 * @see Modules\Bonuses\Domain\Interfaces\QuestEngineInterface
 */
final readonly class QuestEngineService implements QuestEngineInterface
{
    public function __construct() {
        // Dependencies can be injected here as needed
    }

    /**
     * Generates daily quests for a user.
     */
    public function generateDailyQuests(string $userId, string $questDate, int $count = 3): array
    {
        $quests = [];

        // Get user's engagement level to determine difficulty
        $difficulty = $this->calculateQuestDifficulty($userId);
        $recommendedTypes = $this->getRecommendedQuestTypes($userId, $count);

        foreach ($recommendedTypes as $index => $type) {
            $quest = $this->createQuestFromType($type, $difficulty, $questDate);
            $quests[] = $quest;

            // Save to database
            DailyQuestModel::fromDomainEntity($quest);

            // Create user progress entry
            UserQuestProgressModel::create([
                'id' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
                'user_id' => $userId,
                'quest_id' => $quest->id,
                'status' => QuestStatus::IN_PROGRESS->value,
                'progress_json' => $this->initializeProgress($quest),
                'correlation_id' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
                'metadata' => [],
            ]);
        }

        return $quests;
    }

    /**
     * Validates quest completion based on user activity.
     */
    public function validateQuestCompletion(DailyQuest $quest, ActivityScore $activityScore): bool
    {
        $requirements = $quest->requirements;
        $completed = true;

        foreach ($requirements as $key => $target) {
            $current = match ($key) {
                'product_views' => $activityScore->getProductViews(),
                'ar_try_ons' => $activityScore->getArTryOns(),
                'reviews' => $activityScore->getReviews(),
                'cross_vertical_visits' => $activityScore->getCrossVerticalVisits(),
                'purchases' => $activityScore->getPurchases(),
                'shares' => $activityScore->getShares(),
                'referrals' => $activityScore->getReferrals(),
                default => 0,
            };

            if ($current < $target) {
                $completed = false;
                break;
            }
        }

        return $completed;
    }

    /**
     * Calculates quest progress percentage.
     */
    public function calculateQuestProgress(DailyQuest $quest, ActivityScore $activityScore): float
    {
        $requirements = $quest->requirements;
        $totalProgress = 0;
        $requirementCount = count($requirements);

        if ($requirementCount === 0) {
            return 0.0;
        }

        foreach ($requirements as $key => $target) {
            $current = match ($key) {
                'product_views' => $activityScore->getProductViews(),
                'ar_try_ons' => $activityScore->getArTryOns(),
                'reviews' => $activityScore->getReviews(),
                'cross_vertical_visits' => $activityScore->getCrossVerticalVisits(),
                'purchases' => $activityScore->getPurchases(),
                'shares' => $activityScore->getShares(),
                'referrals' => $activityScore->getReferrals(),
                default => 0,
            };

            $percentage = $target > 0 ? ($current / $target) * 100 : 0;
            $totalProgress += min(100, $percentage);
        }

        return $totalProgress / $requirementCount;
    }

    /**
     * Awards quest rewards to the user.
     */
    public function awardQuestRewards(DailyQuest $quest, string $userId): array
    {
        return [
            'bonus_reward' => $quest->bonusReward,
            'hold_reduction' => $quest->holdReductionReward,
            'multiplier_reward' => $quest->multiplierReward,
            'loyalty_points' => $quest->loyaltyPointsReward,
            'total_value' => $quest->getTotalRewardValue(),
        ];
    }

    /**
     * Resets daily quests for a user.
     */
    public function resetDailyQuests(string $userId, string $questDate): int
    {
        // Delete old progress for the same date
        $deleted = UserQuestProgressModel::forUser($userId)
            ->whereHas('quest', fn ($q) => $q->forDate($questDate))
            ->delete();

        return $deleted;
    }

    /**
     * Gets available quest templates for generation.
     */
    public function getQuestTemplates(?string $vertical = null, ?string $difficulty = null): array
    {
        $templates = [
            [
                'type' => 'LOGIN',
                'title' => 'Ежедневный вход',
                'description' => 'Войдите в приложение сегодня',
                'requirements' => [],
                'difficulty' => 'easy',
                'vertical' => null,
            ],
            [
                'type' => 'PRODUCT_VIEW',
                'title' => 'Просмотр товаров',
                'description' => 'Просмотрите 8 товаров в любой категории',
                'requirements' => ['product_views' => 8],
                'difficulty' => 'easy',
                'vertical' => null,
            ],
            [
                'type' => 'PURCHASE',
                'title' => 'Совершите покупку',
                'description' => 'Совершите покупку на любой сумме',
                'requirements' => ['purchases' => 1],
                'difficulty' => 'medium',
                'vertical' => null,
            ],
            [
                'type' => 'REVIEW',
                'title' => 'Оставьте отзыв',
                'description' => 'Оставьте отзыв с фото к покупке',
                'requirements' => ['reviews' => 1],
                'difficulty' => 'medium',
                'vertical' => null,
            ],
            [
                'type' => 'CROSS_VERTICAL',
                'title' => 'Исследуйте вертикали',
                'description' => 'Посетите товары в 3 разных категориях',
                'requirements' => ['cross_vertical_visits' => 3],
                'difficulty' => 'medium',
                'vertical' => null,
            ],
        ];

        if ($vertical !== null) {
            $templates = array_filter($templates, fn ($t) => $t['vertical'] === null || $t['vertical'] === $vertical);
        }

        if ($difficulty !== null) {
            $templates = array_filter($templates, fn ($t) => $t['difficulty'] === $difficulty);
        }

        return array_values($templates);
    }

    /**
     * Creates a sponsored quest for a brand.
     */
    public function createSponsoredQuest(
        QuestType $type,
        string $sponsorName,
        int $sponsorReward,
        string $vertical,
        string $questDate
    ): DailyQuest {
        $template = $this->getQuestTemplateForType($type);
        $quest = DailyQuest::create(
            type: $type,
            title: $template['title'],
            description: $template['description'],
            requirements: $template['requirements'],
            questDate: $questDate,
            vertical: $vertical,
            difficulty: 'medium',
            isSponsored: true,
            sponsorName: $sponsorName,
            sponsorReward: $sponsorReward,
            priority: 100
        );

        DailyQuestModel::fromDomainEntity($quest);

        return $quest;
    }

    /**
     * Gets user's quest completion statistics.
     */
    public function getUserQuestStats(string $userId, int $days = 30): array
    {
        $startDate = date('Y-m-d', strtotime("-{$days} days"));
        $progressRecords = UserQuestProgressModel::forUser($userId)
            ->whereHas('quest', fn ($q) => $q->forDateRange($startDate, date('Y-m-d')))
            ->get();

        $completed = $progressRecords->where('status', 'completed')->count();
        $claimed = $progressRecords->where('status', 'claimed')->count();
        $total = $progressRecords->count();

        return [
            'total_quests' => $total,
            'completed_quests' => $completed,
            'claimed_quests' => $claimed,
            'completion_rate' => $total > 0 ? ($completed / $total) * 100 : 0,
            'claim_rate' => $completed > 0 ? ($claimed / $completed) * 100 : 0,
        ];
    }

    /**
     * Calculates quest difficulty based on user behavior.
     */
    public function calculateQuestDifficulty(string $userId): string
    {
        $stats = $this->getUserQuestStats($userId, 7);

        return match (true) {
            $stats['completion_rate'] >= 80 => 'hard',
            $stats['completion_rate'] >= 50 => 'medium',
            default => 'easy',
        };
    }

    /**
     * Gets recommended quest types for a user.
     */
    public function getRecommendedQuestTypes(string $userId, int $count = 3): array
    {
        $difficulty = $this->calculateQuestDifficulty($userId);
        $templates = $this->getQuestTemplates(null, $difficulty);

        // Shuffle and take count
        shuffle($templates);
        $selected = array_slice($templates, 0, $count);

        return array_map(fn ($t) => QuestType::fromString($t['type']), $selected);
    }

    /**
     * Validates a quest configuration.
     */
    public function validateQuestConfiguration(DailyQuest $quest): bool
    {
        if (empty($quest->title) || empty($quest->description)) {
            throw new DomainException('Quest must have title and description');
        }

        if ($quest->bonusReward < 0 || $quest->holdReductionReward < 0) {
            throw new DomainException('Quest rewards cannot be negative');
        }

        if ($quest->isSponsored && empty($quest->sponsorName)) {
            throw new DomainException('Sponsored quest must have sponsor name');
        }

        return true;
    }

    /**
     * Creates a quest from type template.
     */
    private function createQuestFromType(QuestType $type, string $difficulty, string $questDate): DailyQuest
    {
        $template = $this->getQuestTemplateForType($type);

        return DailyQuest::create(
            type: $type,
            title: $template['title'],
            description: $template['description'],
            requirements: $template['requirements'],
            questDate: $questDate,
            vertical: $template['vertical'],
            difficulty: $difficulty,
            isSponsored: false,
            priority: 0
        );
    }

    /**
     * Gets quest template for a type.
     */
    private function getQuestTemplateForType(QuestType $type): array
    {
        $templates = $this->getQuestTemplates();
        $filtered = array_filter($templates, fn ($t) => $t['type'] === $type->value);

        if (empty($filtered)) {
            throw new DomainException("No template found for quest type: {$type->value}");
        }

        return array_shift($filtered);
    }

    /**
     * Initializes progress JSON for a quest.
     */
    private function initializeProgress(DailyQuest $quest): array
    {
        $progress = [];

        foreach ($quest->requirements as $key => $target) {
            $progress[$key] = [
                'current' => 0,
                'target' => $target,
                'percentage' => 0,
                'completed' => false,
            ];
        }

        return $progress;
    }
}
