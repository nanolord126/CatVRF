<?php

declare(strict_types=1);

namespace App\Domains\Bonuses\Services;

use App\Domains\Bonuses\Models\BonusRule;
use App\Domains\Bonuses\Models\BonusCampaign;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Psr\Log\LoggerInterface;

/**
 * BonusCalculationService - Domain service for bonus calculation
 * 
 * Calculates bonus amounts based on rules, campaigns, and vertical multipliers.
 * Uses caching for rule lookups to improve performance.
 */
final readonly class BonusCalculationService
{
    private const CACHE_TTL_SECONDS = 3600; // 1 hour

    public function __construct(
        private readonly CacheRepository $cache,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Calculate bonus amount for a given order and context
     */
    public function calculateBonus(
        float $orderAmount,
        string $ruleType,
        ?string $verticalCode = null,
        ?string $userType = null,
    ): int {
        $rules = $this->getActiveRules($ruleType, $verticalCode);
        $campaigns = $this->getActiveCampaigns($verticalCode, $userType);

        $baseAmount = $this->calculateFromRules($orderAmount, $rules);
        $campaignBonus = $this->calculateFromCampaigns($orderAmount, $campaigns);

        $totalAmount = $baseAmount + $campaignBonus;

        // Apply vertical multiplier
        $multiplier = $this->getVerticalMultiplier($verticalCode);
        $totalAmount = (int) ($totalAmount * $multiplier);

        $this->logger->debug('Bonus calculated', [
            'order_amount' => $orderAmount,
            'rule_type' => $ruleType,
            'vertical_code' => $verticalCode,
            'base_amount' => $baseAmount,
            'campaign_bonus' => $campaignBonus,
            'multiplier' => $multiplier,
            'total_amount' => $totalAmount,
        ]);

        return $totalAmount;
    }

    /**
     * Calculate bonus from applicable rules
     */
    private function calculateFromRules(float $orderAmount, Collection $rules): int
    {
        $bonusAmount = 0;

        foreach ($rules as $rule) {
            if (!$rule->isEligibleForUser()) {
                continue;
            }

            if ($rule->min_amount && $orderAmount < $rule->min_amount) {
                continue;
            }

            if ($rule->max_amount && $orderAmount > $rule->max_amount) {
                continue;
            }

            $ruleBonus = $rule->calculateBonusAmount($orderAmount);

            if ($ruleBonus > $bonusAmount) {
                $bonusAmount = $ruleBonus;
            }
        }

        return $bonusAmount;
    }

    /**
     * Calculate bonus from active campaigns
     */
    private function calculateFromCampaigns(float $orderAmount, Collection $campaigns): int
    {
        $bonusAmount = 0;

        foreach ($campaigns as $campaign) {
            if (!$campaign->isActiveNow()) {
                continue;
            }

            if (!$campaign->hasBudgetRemaining($orderAmount)) {
                continue;
            }

            if (!$campaign->hasCapacity()) {
                continue;
            }

            $campaignBonus = $campaign->calculateBonusAmount($orderAmount);

            if ($campaignBonus > $bonusAmount) {
                $bonusAmount = $campaignBonus;
            }
        }

        return $bonusAmount;
    }

    /**
     * Get active rules for a given rule type and vertical
     */
    public function getActiveRules(string $ruleType, ?string $verticalCode = null): Collection
    {
        $cacheKey = $this->getRulesCacheKey($ruleType, $verticalCode);

        return $this->cache->remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($ruleType, $verticalCode) {
            return BonusRule::query()
                ->active()
                ->forRuleType($ruleType)
                ->forVertical($verticalCode)
                ->byPriority()
                ->get();
        });
    }

    /**
     * Get active campaigns for a given vertical and user segment
     */
    public function getActiveCampaigns(?string $verticalCode = null, ?string $userType = null): Collection
    {
        $cacheKey = $this->getCampaignsCacheKey($verticalCode, $userType);

        return $this->cache->remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($verticalCode, $userType) {
            return BonusCampaign::query()
                ->active()
                ->forVertical($verticalCode)
                ->forSegment($userType)
                ->byPriority()
                ->get();
        });
    }

    /**
     * Get vertical multiplier from config
     */
    public function getVerticalMultiplier(?string $verticalCode): float
    {
        if (!$verticalCode) {
            return 1.0;
        }

        return config("bonuses.vertical_multipliers.{$verticalCode}", 1.0);
    }

    /**
     * Calculate turnover bonus based on tiers
     */
    public function calculateTurnoverBonus(float $totalTurnover, ?string $verticalCode = null): int
    {
        $config = config('bonuses.rules.turnover', []);
        $tiers = $config['tiers'] ?? [];

        $bonusPercentage = 0.0;

        foreach ($tiers as $tier) {
            if ($totalTurnover >= $tier['min_turnover']) {
                $bonusPercentage = $tier['percentage'];
            }
        }

        $bonusAmount = ($totalTurnover * $bonusPercentage) / 100;
        $multiplier = $this->getVerticalMultiplier($verticalCode);

        return (int) ($bonusAmount * $multiplier);
    }

    /**
     * Calculate loyalty bonus with streak multiplier
     */
    public function calculateLoyaltyBonus(
        float $orderAmount,
        int $consecutiveMonths,
        ?string $verticalCode = null,
    ): int {
        $config = config('bonuses.rules.loyalty', []);
        $basePercentage = $config['repeat_purchase_percentage'] ?? 1.5;
        $streakMultiplier = $config['streak_multiplier'] ?? 0.5;
        $maxStreakMultiplier = $config['max_streak_multiplier'] ?? 5.0;

        $streakBonus = min($consecutiveMonths * $streakMultiplier, $maxStreakMultiplier);
        $totalPercentage = $basePercentage + $streakBonus;

        $bonusAmount = ($orderAmount * $totalPercentage) / 100;
        $multiplier = $this->getVerticalMultiplier($verticalCode);

        return (int) ($bonusAmount * $multiplier);
    }

    /**
     * Clear rules cache
     */
    public function clearRulesCache(?string $ruleType = null, ?string $verticalCode = null): void
    {
        if ($ruleType && $verticalCode) {
            $this->cache->forget($this->getRulesCacheKey($ruleType, $verticalCode));
        } else {
            $this->cache->forgetMatching('bonus_rules:*');
        }
    }

    /**
     * Clear campaigns cache
     */
    public function clearCampaignsCache(?string $verticalCode = null, ?string $userType = null): void
    {
        if ($verticalCode && $userType) {
            $this->cache->forget($this->getCampaignsCacheKey($verticalCode, $userType));
        } else {
            $this->cache->forgetMatching('bonus_campaigns:*');
        }
    }

    private function getRulesCacheKey(string $ruleType, ?string $verticalCode): string
    {
        $vertical = $verticalCode ?? 'global';
        
        return "bonus_rules:{$ruleType}:{$vertical}";
    }

    private function getCampaignsCacheKey(?string $verticalCode, ?string $userType): string
    {
        $vertical = $verticalCode ?? 'global';
        $segment = $userType ?? 'all';
        
        return "bonus_campaigns:{$vertical}:{$segment}";
    }
}
