<?php

declare(strict_types=1);

namespace Modules\Bonuses\Infrastructure\Services;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Modules\Bonuses\Domain\Enums\LoyaltyTier;
use Modules\Bonuses\Domain\Interfaces\LoyaltyCalculatorInterface;
use Modules\Bonuses\Domain\ValueObjects\BonusAmount;
use Modules\Bonuses\Domain\ValueObjects\LoyaltyStatus;

/**
 * Class LoyaltyCalculator
 *
 * Implementation of LoyaltyCalculatorInterface with loyalty calculations.
 * Handles point accrual, tier progression, and benefit calculations.
 * Integrates with config for loyalty rules and thresholds.
 */
final class LoyaltyCalculator implements LoyaltyCalculatorInterface
{
    public function __construct(
        private readonly ConfigRepository $config
    ) {}

    public function calculatePointsEarned(string $ownerId, float $transactionAmount, string $activityType, array $context = []): int
    {
        $baseRate = $this->config->get('bonuses.loyalty.base_rate', 0.01); // 1% by default

        // Activity type multipliers
        $multipliers = $this->config->get('bonuses.loyalty.activity_multipliers', [
            'purchase' => 1.0,
            'review' => 0.5,
            'referral' => 5.0,
            'action' => 0.1,
        ]);

        $multiplier = $multipliers[$activityType] ?? 1.0;

        // Vertical-specific multipliers
        $vertical = $context['vertical'] ?? null;
        if ($vertical) {
            $verticalMultipliers = $this->config->get('bonuses.loyalty.vertical_multipliers', []);
            $verticalMultiplier = $verticalMultipliers[$vertical] ?? 1.0;
            $multiplier *= $verticalMultiplier;
        }

        $points = (int) floor($transactionAmount * $baseRate * $multiplier);

        return max(1, $points); // Minimum 1 point
    }

    public function calculateTier(int $totalPoints): LoyaltyTier
    {
        return LoyaltyTier::fromPoints($totalPoints);
    }

    public function calculateNewStatus(string $ownerId, LoyaltyStatus $currentStatus, int $pointsToAdd): LoyaltyStatus
    {
        $newStatus = $currentStatus->addPoints($pointsToAdd);

        return $newStatus;
    }

    public function calculatePointsToNextTier(LoyaltyStatus $currentStatus): int
    {
        return $currentStatus->getPointsToNextTier();
    }

    public function calculateDiscountPercentage(LoyaltyStatus $status, ?string $vertical = null): float
    {
        $baseDiscount = $status->tier->getDiscountPercentage();

        // Vertical-specific discounts
        if ($vertical) {
            $verticalDiscounts = $this->config->get('bonuses.loyalty.vertical_discounts', []);
            $verticalBonus = $verticalDiscounts[$vertical] ?? 0;

            return min(100, $baseDiscount + $verticalBonus);
        }

        return $baseDiscount;
    }

    public function applyBonusMultiplier(string $ownerId, BonusAmount $baseAmount, LoyaltyStatus $status): BonusAmount
    {
        $multiplier = $status->tier->getBonusMultiplier();
        $multipliedAmount = (int) floor($baseAmount->getAmount() * $multiplier);

        return new BonusAmount($multipliedAmount);
    }

    public function calculatePointDecay(string $ownerId, LoyaltyStatus $status): int
    {
        $decayEnabled = $this->config->get('bonuses.loyalty.decay.enabled', false);

        if (!$decayEnabled) {
            return 0;
        }

        $decayPeriod = $this->config->get('bonuses.loyalty.decay.period_days', 365);
        $decayRate = $this->config->get('bonuses.loyalty.decay.rate_percent', 10);

        // TODO: Implement actual decay calculation based on last activity
        // For now, return 0 (no decay)
        return 0;
    }

    public function calculateBonusExpirationDays(LoyaltyStatus $status): int
    {
        return $status->tier->getBonusExpirationDays();
    }

    public function calculateMonthlyBonusCap(LoyaltyStatus $status): int
    {
        return $status->tier->getMonthlyBonusCap();
    }

    public function hasTierChanged(LoyaltyStatus $currentStatus, LoyaltyStatus $previousStatus): bool
    {
        return $currentStatus->hasTierChanged($previousStatus);
    }

    public function calculatePointsFromBonus(string $ownerId, BonusAmount $bonusAmount, LoyaltyStatus $status): int
    {
        $conversionRate = $this->config->get('bonuses.loyalty.bonus_to_points_rate', 0.1); // 10% of bonus as points

        $points = (int) floor($bonusAmount->getAmount() * $conversionRate);

        return max(1, $points); // Minimum 1 point
    }

    public function calculateEffectiveBonus(string $ownerId, BonusAmount $requestedAmount, LoyaltyStatus $status, array $context = []): BonusAmount
    {
        $effectiveAmount = $requestedAmount;

        // Apply tier multiplier
        $effectiveAmount = $this->applyBonusMultiplier($ownerId, $effectiveAmount, $status);

        // Apply vertical-specific bonuses
        $vertical = $context['vertical'] ?? null;
        if ($vertical) {
            $verticalBonuses = $this->config->get('bonuses.loyalty.vertical_bonus_multipliers', []);
            $verticalMultiplier = $verticalBonuses[$vertical] ?? 1.0;

            $multipliedAmount = (int) floor($effectiveAmount->getAmount() * $verticalMultiplier);
            $effectiveAmount = new BonusAmount($multipliedAmount);
        }

        // Apply promotional multipliers
        $isPromotion = $context['is_promotion'] ?? false;
        if ($isPromotion) {
            $promotionMultiplier = $this->config->get('bonuses.loyalty.promotion_multiplier', 1.5);

            $multipliedAmount = (int) floor($effectiveAmount->getAmount() * $promotionMultiplier);
            $effectiveAmount = new BonusAmount($multipliedAmount);
        }

        return $effectiveAmount;
    }

    public function isEligibleForBenefit(string $ownerId, string $benefit, LoyaltyStatus $status): bool
    {
        return match ($benefit) {
            'priority_support' => $status->hasPrioritySupport(),
            'exclusive_promotions' => $status->hasExclusivePromotions(),
            'dedicated_manager' => $status->hasDedicatedManager(),
            'extended_expiration' => $status->tier->getBonusExpirationDays() > 365,
            'higher_multiplier' => $status->tier->getBonusMultiplier() > 1.0,
            default => false,
        };
    }

    public function getBenefitsSummary(string $ownerId, LoyaltyStatus $status): array
    {
        $benefits = [];

        $benefits['tier'] = $status->tier->value;
        $benefits['discount_percentage'] = $status->discountPercentage;
        $benefits['bonus_multiplier'] = $status->tier->getBonusMultiplier();
        $benefits['bonus_expiration_days'] = $status->tier->getBonusExpirationDays();
        $benefits['monthly_bonus_cap'] = $status->tier->getMonthlyBonusCap();

        $benefits['available_benefits'] = [
            'priority_support' => $this->isEligibleForBenefit($ownerId, 'priority_support', $status),
            'exclusive_promotions' => $this->isEligibleForBenefit($ownerId, 'exclusive_promotions', $status),
            'dedicated_manager' => $this->isEligibleForBenefit($ownerId, 'dedicated_manager', $status),
            'extended_expiration' => $this->isEligibleForBenefit($ownerId, 'extended_expiration', $status),
            'higher_multiplier' => $this->isEligibleForBenefit($ownerId, 'higher_multiplier', $status),
        ];

        $benefits['points_to_next_tier'] = $this->calculatePointsToNextTier($status);
        $benefits['total_points'] = $status->points;

        return $benefits;
    }

    public function calculateTierTrajectory(string $ownerId, LoyaltyStatus $status, float $monthlyPointRate): array
    {
        $trajectory = [];
        $currentPoints = $status->points;
        $currentTier = $status->tier;

        $allTiers = LoyaltyTier::getOrderedTiers();
        $currentIndex = array_search($currentTier, $allTiers, true);

        $months = 0;
        $projectedPoints = $currentPoints;

        foreach ($allTiers as $index => $tier) {
            if ($index <= $currentIndex) {
                continue; // Skip current and past tiers
            }

            $pointsNeeded = $tier->getMinPoints() - $projectedPoints;
            $monthsToReach = $monthlyPointRate > 0 ? (int) ceil($pointsNeeded / $monthlyPointRate) : PHP_INT_MAX;

            $trajectory[] = [
                'tier' => $tier->value,
                'points_needed' => $pointsNeeded,
                'months_to_reach' => $monthsToReach,
                'discount_percentage' => $tier->getDiscountPercentage(),
                'bonus_multiplier' => $tier->getBonusMultiplier(),
            ];

            $projectedPoints += $pointsNeeded;
            $months += $monthsToReach;
        }

        return [
            'current_tier' => $currentTier->value,
            'current_points' => $currentPoints,
            'monthly_point_rate' => $monthlyPointRate,
            'projected_tiers' => $trajectory,
        ];
    }

    public function resetMonthlyTracking(LoyaltyStatus $status): LoyaltyStatus
    {
        return $status->resetMonthlyPoints();
    }
}
