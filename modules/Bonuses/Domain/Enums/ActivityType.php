<?php

declare(strict_types=1);

namespace Modules\Bonuses\Domain\Enums;

/**
 * Enum ActivityType
 *
 * Defines the type of user activity for daily tracking.
 * Used in DailyActivityLog for streak calculation and multipliers.
 */
enum ActivityType: string
{
    case LOGIN = 'login';
    case PRODUCT_VIEW = 'product_view';
    case AR_TRY_ON = 'ar_tryon';
    case REVIEW = 'review';
    case PURCHASE = 'purchase';
    case CROSS_VERTICAL = 'cross_vertical';
    case QUEST_COMPLETION = 'quest_completion';
    case SHARE = 'share';

    /**
     * Gets the activity score contribution for this type.
     */
    public function getScoreContribution(): int
    {
        return match ($this) {
            self::LOGIN => 5,
            self::PRODUCT_VIEW => 10,
            self::AR_TRY_ON => 20,
            self::REVIEW => 25,
            self::PURCHASE => 30,
            self::CROSS_VERTICAL => 15,
            self::QUEST_COMPLETION => 20,
            self::SHARE => 10,
        };
    }

    /**
     * Checks if this activity counts toward the daily threshold.
     */
    public function countsTowardThreshold(): bool
    {
        return match ($this) {
            self::LOGIN => false, // Login is required but doesn't count as an action
            self::PRODUCT_VIEW, self::AR_TRY_ON, self::REVIEW,
            self::PURCHASE, self::CROSS_VERTICAL, self::QUEST_COMPLETION,
            self::SHARE => true,
        };
    }

    /**
     * Creates activity type from string.
     */
    public static function fromString(string $value): self
    {
        return self::tryFrom($value) ?? self::LOGIN;
    }
}
