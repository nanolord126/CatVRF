<?php

declare(strict_types=1);

namespace Modules\Bonuses\Domain\Enums;

/**
 * Enum QuestType
 *
 * Defines the type of daily quest in CatFloat.
 * Each quest type has different requirements and rewards.
 */
enum QuestType: string
{
    case PRODUCT_VIEW = 'product_view';
    case AR_TRY_ON = 'ar_tryon';
    case REVIEW = 'review';
    case PURCHASE = 'purchase';
    case CROSS_VERTICAL = 'cross_vertical';
    case LOGIN = 'login';
    case SHARE = 'share';
    case REFERRAL = 'referral';

    /**
     * Gets the default reward for this quest type.
     */
    public function getDefaultReward(): int
    {
        return match ($this) {
            self::PRODUCT_VIEW => 1500, // 15 ₽
            self::AR_TRY_ON => 2500, // 25 ₽
            self::REVIEW => 5000, // 50 ₽
            self::PURCHASE => 10000, // 100 ₽
            self::CROSS_VERTICAL => 3000, // 30 ₽
            self::LOGIN => 500, // 5 ₽
            self::SHARE => 2000, // 20 ₽
            self::REFERRAL => 50000, // 500 ₽
        };
    }

    /**
     * Gets the default hold days reduction for this quest type.
     */
    public function getDefaultHoldReduction(): int
    {
        return match ($this) {
            self::PRODUCT_VIEW => 0,
            self::AR_TRY_ON => 1,
            self::REVIEW => 2,
            self::PURCHASE => 3,
            self::CROSS_VERTICAL => 2,
            self::LOGIN => 0,
            self::SHARE => 1,
            self::REFERRAL => 5,
        };
    }

    /**
     * Gets the default bonus multiplier for this quest type.
     */
    public function getDefaultBonusMultiplier(): float
    {
        return match ($this) {
            self::PRODUCT_VIEW => 1.0,
            self::AR_TRY_ON => 1.15,
            self::REVIEW => 1.2,
            self::PURCHASE => 1.25,
            self::CROSS_VERTICAL => 1.25,
            self::LOGIN => 1.0,
            self::SHARE => 1.1,
            self::REFERRAL => 1.5,
        };
    }

    /**
     * Creates quest type from string.
     */
    public static function fromString(string $value): self
    {
        return self::tryFrom($value) ?? self::PRODUCT_VIEW;
    }
}
