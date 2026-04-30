<?php

declare(strict_types=1);

namespace Modules\Bonuses\Domain\Enums;

use InvalidArgumentException;

/**
 * Enum LoyaltyTier
 *
 * Represents customer loyalty levels with progressive benefits and requirements.
 * Each tier has specific point thresholds, discount percentages, and exclusive perks.
 * Tier transitions are calculated based on accumulated loyalty points over time.
 */
enum LoyaltyTier: string
{
    /**
     * Entry-level tier for all new customers.
     * No minimum points required, base benefits only.
     */
    case BRONZE = 'bronze';

    /**
     * First loyalty tier requiring 1,000 points.
     * Provides 5% discount on eligible services.
     */
    case SILVER = 'silver';

    /**
     * Mid-tier loyalty requiring 5,000 points.
     * Provides 10% discount and priority support.
     */
    case GOLD = 'gold';

    /**
     * Premium tier requiring 10,000 points.
     * Provides 15% discount, priority support, and exclusive promotions.
     */
    case PLATINUM = 'platinum';

    /**
     * VIP tier requiring 50,000 points.
     * Provides 20% discount, dedicated account manager, and exclusive access.
     */
    case DIAMOND = 'diamond';

    /**
     * Verification wrapper strictly enforcing valid tier values.
     *
     * @param  string  $value  The tier value to validate.
     */
    public static function isValid(string $value): bool
    {
        return in_array($value, array_column(self::cases(), 'value'), true);
    }

    /**
     * Transforms string value to enum with proper error handling.
     *
     * @param  string  $value  The tier value to convert.
     * @throws InvalidArgumentException When value is invalid.
     */
    public static function fromString(string $value): self
    {
        $tier = self::tryFrom($value);
        
        if ($tier === null) {
            throw new InvalidArgumentException(
                sprintf('Invalid loyalty tier: %s. Valid tiers are: %s', 
                    $value,
                    implode(', ', array_column(self::cases(), 'value'))
                )
            );
        }
        
        return $tier;
    }

    /**
     * Returns the minimum points required to achieve this tier.
     * Points are calculated from completed transactions and bonus awards.
     */
    public function getMinPoints(): int
    {
        return match ($this) {
            self::BRONZE => 0,
            self::SILVER => 1000,
            self::GOLD => 5000,
            self::PLATINUM => 10000,
            self::DIAMOND => 50000,
        };
    }

    /**
     * Returns the discount percentage for this tier.
     * Applied to eligible services and products.
     */
    public function getDiscountPercentage(): int
    {
        return match ($this) {
            self::BRONZE => 0,
            self::SILVER => 5,
            self::GOLD => 10,
            self::PLATINUM => 15,
            self::DIAMOND => 20,
        };
    }

    /**
     * Returns the bonus multiplier for this tier.
     * Higher tiers earn bonus points faster on transactions.
     */
    public function getBonusMultiplier(): float
    {
        return match ($this) {
            self::BRONZE => 1.0,
            self::SILVER => 1.1,
            self::GOLD => 1.25,
            self::PLATINUM => 1.5,
            self::DIAMOND => 2.0,
        };
    }

    /**
     * Determines if this tier has priority support.
     * Higher tiers receive faster response times.
     */
    public function hasPrioritySupport(): bool
    {
        return match ($this) {
            self::GOLD, self::PLATINUM, self::DIAMOND => true,
            self::BRONZE, self::SILVER => false,
        };
    }

    /**
     * Determines if this tier has exclusive promotions access.
     * Higher tiers receive special offers not available to lower tiers.
     */
    public function hasExclusivePromotions(): bool
    {
        return match ($this) {
            self::PLATINUM, self::DIAMOND => true,
            self::BRONZE, self::SILVER, self::GOLD => false,
        };
    }

    /**
     * Determines if this tier has a dedicated account manager.
     * Only the highest tier receives this benefit.
     */
    public function hasDedicatedManager(): bool
    {
        return $this === self::DIAMOND;
    }

    /**
     * Returns the maximum bonus expiration days for this tier.
     * Higher tiers have longer bonus expiration periods.
     */
    public function getBonusExpirationDays(): int
    {
        return match ($this) {
            self::BRONZE => 180,
            self::SILVER => 270,
            self::GOLD => 365,
            self::PLATINUM => 540,
            self::DIAMOND => 730,
        };
    }

    /**
     * Returns the monthly bonus cap for this tier in smallest currency unit.
     * Higher tiers can earn more bonuses per month.
     */
    public function getMonthlyBonusCap(): int
    {
        return match ($this) {
            self::BRONZE => 1000000, // 10,000.00
            self::SILVER => 2500000, // 25,000.00
            self::GOLD => 5000000, // 50,000.00
            self::PLATINUM => 10000000, // 100,000.00
            self::DIAMOND => 25000000, // 250,000.00
        };
    }

    /**
     * Determines tier based on accumulated points.
     *
     * @param  int  $points  The total loyalty points.
     */
    public static function fromPoints(int $points): self
    {
        return match (true) {
            $points >= 50000 => self::DIAMOND,
            $points >= 10000 => self::PLATINUM,
            $points >= 5000 => self::GOLD,
            $points >= 1000 => self::SILVER,
            default => self::BRONZE,
        };
    }

    /**
     * Returns the next tier in the progression.
     * Returns null if already at the maximum tier.
     */
    public function getNextTier(): ?self
    {
        return match ($this) {
            self::BRONZE => self::SILVER,
            self::SILVER => self::GOLD,
            self::GOLD => self::PLATINUM,
            self::PLATINUM => self::DIAMOND,
            self::DIAMOND => null,
        };
    }

    /**
     * Returns the previous tier in the progression.
     * Returns null if already at the minimum tier.
     */
    public function getPreviousTier(): ?self
    {
        return match ($this) {
            self::BRONZE => null,
            self::SILVER => self::BRONZE,
            self::GOLD => self::SILVER,
            self::PLATINUM => self::GOLD,
            self::DIAMOND => self::PLATINUM,
        };
    }

    /**
     * Calculates points needed to reach the next tier.
     * Returns 0 if already at maximum tier.
     */
    public function getPointsToNextTier(int $currentPoints): int
    {
        $nextTier = $this->getNextTier();
        
        if ($nextTier === null) {
            return 0;
        }
        
        $nextTierMin = $nextTier->getMinPoints();
        $pointsNeeded = $nextTierMin - $currentPoints;
        
        return max(0, $pointsNeeded);
    }

    /**
     * Returns the human-readable label for this tier.
     * Used in UI displays and communications.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::BRONZE => 'Bronze',
            self::SILVER => 'Silver',
            self::GOLD => 'Gold',
            self::PLATINUM => 'Platinum',
            self::DIAMOND => 'Diamond',
        };
    }

    /**
     * Returns array of all valid tier values for validation.
     */
    public static function getValidValues(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Returns all tiers ordered from lowest to highest.
     */
    public static function getOrderedTiers(): array
    {
        return [
            self::BRONZE,
            self::SILVER,
            self::GOLD,
            self::PLATINUM,
            self::DIAMOND,
        ];
    }

    /**
     * Determines if this tier is higher than the given tier.
     */
    public function isHigherThan(self $other): bool
    {
        $ordered = self::getOrderedTiers();
        $thisIndex = array_search($this, $ordered, true);
        $otherIndex = array_search($other, $ordered, true);
        
        return $thisIndex > $otherIndex;
    }

    /**
     * Determines if this tier is lower than the given tier.
     */
    public function isLowerThan(self $other): bool
    {
        return !$this->isHigherThan($other) && $this !== $other;
    }
}
