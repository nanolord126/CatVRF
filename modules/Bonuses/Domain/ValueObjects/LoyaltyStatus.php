<?php

declare(strict_types=1);

namespace Modules\Bonuses\Domain\ValueObjects;

use InvalidArgumentException;
use Modules\Bonuses\Domain\Enums\LoyaltyTier;

/**
 * Value Object LoyaltyStatus
 *
 * Represents the current loyalty status of an entity (user/customer).
 * Encapsulates tier, points, and calculated benefits in an immutable structure.
 * Provides methods for tier progression and benefit calculations.
 */
final readonly class LoyaltyStatus
{
    /**
     * @param  LoyaltyTier  $tier  Current loyalty tier.
     * @param  int  $points  Total accumulated loyalty points.
     * @param  int  $pointsEarnedThisMonth  Points earned in current month (for monthly caps).
     * @param  float  $discountPercentage  Current discount percentage based on tier.
     * @param  int  $totalBonusesEarned  Total bonuses earned lifetime.
     * @param  int  $totalBonusesConsumed  Total bonuses consumed lifetime.
     */
    public function __construct(
        public LoyaltyTier $tier,
        public int $points,
        public int $pointsEarnedThisMonth = 0,
        public float $discountPercentage = 0.0,
        public int $totalBonusesEarned = 0,
        public int $totalBonusesConsumed = 0
    ) {
        $this->validate();
    }

    /**
     * Validates the loyalty status data.
     */
    private function validate(): void
    {
        if ($this->points < 0) {
            throw new InvalidArgumentException('Points cannot be negative');
        }

        if ($this->pointsEarnedThisMonth < 0) {
            throw new InvalidArgumentException('Monthly points cannot be negative');
        }

        if ($this->discountPercentage < 0 || $this->discountPercentage > 100) {
            throw new InvalidArgumentException('Discount percentage must be between 0 and 100');
        }

        if ($this->totalBonusesEarned < 0) {
            throw new InvalidArgumentException('Total bonuses earned cannot be negative');
        }

        if ($this->totalBonusesConsumed < 0) {
            throw new InvalidArgumentException('Total bonuses consumed cannot be negative');
        }

        if ($this->totalBonusesConsumed > $this->totalBonusesEarned) {
            throw new InvalidArgumentException('Consumed bonuses cannot exceed earned bonuses');
        }
    }

    /**
     * Creates a new LoyaltyStatus from raw data.
     * Factory method for reconstructing from persistence.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            tier: LoyaltyTier::fromString($data['tier']),
            points: (int) $data['points'],
            pointsEarnedThisMonth: (int) ($data['points_earned_this_month'] ?? 0),
            discountPercentage: (float) ($data['discount_percentage'] ?? 0),
            totalBonusesEarned: (int) ($data['total_bonuses_earned'] ?? 0),
            totalBonusesConsumed: (int) ($data['total_bonuses_consumed'] ?? 0),
        );
    }

    /**
     * Creates a new LoyaltyStatus with recalculated tier based on points.
     */
    public static function fromPoints(int $points): self
    {
        $tier = LoyaltyTier::fromPoints($points);
        
        return new self(
            tier: $tier,
            points: $points,
            discountPercentage: $tier->getDiscountPercentage(),
        );
    }

    /**
     * Adds points to the current status and recalculates tier if needed.
     */
    public function addPoints(int $points): self
    {
        $newPoints = $this->points + $points;
        $newTier = LoyaltyTier::fromPoints($newPoints);
        $newMonthlyPoints = $this->pointsEarnedThisMonth + $points;
        
        return new self(
            tier: $newTier,
            points: $newPoints,
            pointsEarnedThisMonth: $newMonthlyPoints,
            discountPercentage: $newTier->getDiscountPercentage(),
            totalBonusesEarned: $this->totalBonusesEarned,
            totalBonusesConsumed: $this->totalBonusesConsumed,
        );
    }

    /**
     * Removes points from the current status and recalculates tier if needed.
     */
    public function removePoints(int $points): self
    {
        if ($points > $this->points) {
            throw new InvalidArgumentException('Cannot remove more points than available');
        }
        
        $newPoints = $this->points - $points;
        $newTier = LoyaltyTier::fromPoints($newPoints);
        
        return new self(
            tier: $newTier,
            points: $newPoints,
            pointsEarnedThisMonth: max(0, $this->pointsEarnedThisMonth - $points),
            discountPercentage: $newTier->getDiscountPercentage(),
            totalBonusesEarned: $this->totalBonusesEarned,
            totalBonusesConsumed: $this->totalBonusesConsumed,
        );
    }

    /**
     * Records a bonus award and updates the status.
     */
    public function recordBonusAward(): self
    {
        return new self(
            tier: $this->tier,
            points: $this->points,
            pointsEarnedThisMonth: $this->pointsEarnedThisMonth,
            discountPercentage: $this->discountPercentage,
            totalBonusesEarned: $this->totalBonusesEarned + 1,
            totalBonusesConsumed: $this->totalBonusesConsumed,
        );
    }

    /**
     * Records a bonus consumption and updates the status.
     */
    public function recordBonusConsumption(): self
    {
        return new self(
            tier: $this->tier,
            points: $this->points,
            pointsEarnedThisMonth: $this->pointsEarnedThisMonth,
            discountPercentage: $this->discountPercentage,
            totalBonusesEarned: $this->totalBonusesEarned,
            totalBonusesConsumed: $this->totalBonusesConsumed + 1,
        );
    }

    /**
     * Resets monthly points (typically called at the start of a new month).
     */
    public function resetMonthlyPoints(): self
    {
        return new self(
            tier: $this->tier,
            points: $this->points,
            pointsEarnedThisMonth: 0,
            discountPercentage: $this->discountPercentage,
            totalBonusesEarned: $this->totalBonusesEarned,
            totalBonusesConsumed: $this->totalBonusesConsumed,
        );
    }

    /**
     * Checks if the user has reached the monthly bonus cap.
     */
    public function hasReachedMonthlyCap(int $bonusAmount): bool
    {
        $monthlyCap = $this->tier->getMonthlyBonusCap();
        
        return $this->pointsEarnedThisMonth + $bonusAmount > $monthlyCap;
    }

    /**
     * Calculates remaining bonus capacity for the current month.
     */
    public function getRemainingMonthlyCapacity(): int
    {
        $monthlyCap = $this->tier->getMonthlyBonusCap();
        
        return max(0, $monthlyCap - $this->pointsEarnedThisMonth);
    }

    /**
     * Calculates points needed to reach the next tier.
     */
    public function getPointsToNextTier(): int
    {
        return $this->tier->getPointsToNextTier($this->points);
    }

    /**
     * Checks if the tier has changed compared to the provided status.
     */
    public function hasTierChanged(self $other): bool
    {
        return $this->tier !== $other->tier;
    }

    /**
     * Determines if this is an upgrade compared to the provided status.
     */
    public function isUpgradeFrom(self $other): bool
    {
        return $this->tier->isHigherThan($other->tier);
    }

    /**
     * Determines if this is a downgrade compared to the provided status.
     */
    public function isDowngradeFrom(self $other): bool
    {
        return $this->tier->isLowerThan($other->tier);
    }

    /**
     * Converts the status to an array for persistence.
     */
    public function toArray(): array
    {
        return [
            'tier' => $this->tier->value,
            'points' => $this->points,
            'points_earned_this_month' => $this->pointsEarnedThisMonth,
            'discount_percentage' => $this->discountPercentage,
            'total_bonuses_earned' => $this->totalBonusesEarned,
            'total_bonuses_consumed' => $this->totalBonusesConsumed,
        ];
    }

    /**
     * Calculates the effective bonus amount after applying tier multiplier.
     */
    public function calculateEffectiveBonus(int $baseAmount): int
    {
        $multiplier = $this->tier->getBonusMultiplier();
        
        return (int) floor($baseAmount * $multiplier);
    }

    /**
     * Checks if the user has priority support based on tier.
     */
    public function hasPrioritySupport(): bool
    {
        return $this->tier->hasPrioritySupport();
    }

    /**
     * Checks if the user has access to exclusive promotions.
     */
    public function hasExclusivePromotions(): bool
    {
        return $this->tier->hasExclusivePromotions();
    }

    /**
     * Checks if the user has a dedicated account manager.
     */
    public function hasDedicatedManager(): bool
    {
        return $this->tier->hasDedicatedManager();
    }

    /**
     * Returns the bonus expiration days based on tier.
     */
    public function getBonusExpirationDays(): int
    {
        return $this->tier->getBonusExpirationDays();
    }
}
