<?php

declare(strict_types=1);

namespace App\Domains\Bonuses\ValueObjects;

/**
 * BonusSource - ValueObject for bonus source types
 * 
 * Defines all possible sources where bonuses can originate from.
 * Used for tracking and analytics.
 */
final readonly class BonusSource
{
    private const PURCHASE = 'purchase';
    private const REFERRAL = 'referral';
    private const QUEST = 'quest';
    private const STREAK_BONUS = 'streak_bonus';
    private const CROSS_VERTICAL = 'cross_vertical';
    private const PROMO_CODE = 'promo_code';
    private const CAMPAIGN = 'campaign';
    private const INSTANT_UNLOCK = 'instant_unlock';
    private const MARKETPLACE_SALE = 'marketplace_sale';
    private const MARKETPLACE_PURCHASE = 'marketplace_purchase';
    private const ADMIN_ADJUSTMENT = 'admin_adjustment';
    private const YIELD = 'yield';

    private string $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function purchase(): self
    {
        return new self(self::PURCHASE);
    }

    public static function referral(): self
    {
        return new self(self::REFERRAL);
    }

    public static function quest(): self
    {
        return new self(self::QUEST);
    }

    public static function streakBonus(): self
    {
        return new self(self::STREAK_BONUS);
    }

    public static function crossVertical(): self
    {
        return new self(self::CROSS_VERTICAL);
    }

    public static function promoCode(): self
    {
        return new self(self::PROMO_CODE);
    }

    public static function campaign(): self
    {
        return new self(self::CAMPAIGN);
    }

    public static function instantUnlock(): self
    {
        return new self(self::INSTANT_UNLOCK);
    }

    public static function marketplaceSale(): self
    {
        return new self(self::MARKETPLACE_SALE);
    }

    public static function marketplacePurchase(): self
    {
        return new self(self::MARKETPLACE_PURCHASE);
    }

    public static function adminAdjustment(): self
    {
        return new self(self::ADMIN_ADJUSTMENT);
    }

    public static function yield(): self
    {
        return new self(self::YIELD);
    }

    public static function fromString(string $value): self
    {
        $validSources = [
            self::PURCHASE,
            self::REFERRAL,
            self::QUEST,
            self::STREAK_BONUS,
            self::CROSS_VERTICAL,
            self::PROMO_CODE,
            self::CAMPAIGN,
            self::INSTANT_UNLOCK,
            self::MARKETPLACE_SALE,
            self::MARKETPLACE_PURCHASE,
            self::ADMIN_ADJUSTMENT,
            self::YIELD,
        ];

        if (!in_array($value, $validSources, true)) {
            throw new \InvalidArgumentException("Invalid bonus source: {$value}");
        }

        return new self($value);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(BonusSource $other): bool
    {
        return $this->value === $other->value;
    }

    public function isPurchase(): bool
    {
        return $this->value === self::PURCHASE;
    }

    public function isReferral(): bool
    {
        return $this->value === self::REFERRAL;
    }

    public function isQuest(): bool
    {
        return $this->value === self::QUEST;
    }

    public function isStreakBonus(): bool
    {
        return $this->value === self::STREAK_BONUS;
    }

    public function isCrossVertical(): bool
    {
        return $this->value === self::CROSS_VERTICAL;
    }

    public function isYield(): bool
    {
        return $this->value === self::YIELD;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
