<?php

declare(strict_types=1);

namespace App\Domains\Bonuses\Enums;

/**
 * Типы бонусных начислений.
 *
 * Определяет источник и причину начисления бонусов.
 * Используется для аналитики, A/B-тестирования и fraud detection.
 */
enum BonusType: string
{
    /** Бонус за реферальную программу (приглашение друзей). */
    case REFERRAL = 'referral';

    /** Бонус за первую покупку нового пользователя. */
    case FIRST_PURCHASE = 'first_purchase';

    /** Бонус за оборот (процент от потраченной суммы). */
    case TURNOVER = 'turnover';

    /** Бонус за лояльность (повторные покупки, streak). */
    case LOYALTY = 'loyalty';

    /** Промо-бонус (акции, купоны, специальные предложения). */
    case PROMO = 'promo';

    /** Бонус за использование AI-конструктора. */
    case AI_CONSTRUCTOR_USAGE = 'ai_constructor_usage';

    /** Бонус за использование AR-примерки. */
    case AR_TRY_ON = 'ar_try_on';

    /** Бонус за оставление отзыва. */
    case REVIEW = 'review';

    /** Бонус за регистрацию в системе. */
    case REGISTRATION = 'registration';

    /** Бонус за миграцию из старой системы. */
    case MIGRATION = 'migration';

    /** Кастомный тип бонуса (для спец. кампаний). */
    case CUSTOM = 'custom';

    /** Бонус за участие в опросе/исследовании. */
    case SURVEY = 'survey';

    /** Бонус за социальные действия (подписка, репост и т.д.). */
    case SOCIAL = 'social';

    /** Бонус за завершение onboarding. */
    case ONBOARDING = 'onboarding';

    /** Доход от float (процент на деньги в холде). ТОЛЬКО БОНУСЫ, не деньги (по законам РФ). */
    case FLOAT_YIELD = 'float_yield';

    /**
     * Проверить, требует ли тип бонуса hold (заморозку перед зачислением).
     */
    public function requiresHold(): bool
    {
        return match ($this) {
            self::REFERRAL, self::FIRST_PURCHASE, self::TURNOVER, self::LOYALTY => true,
            self::PROMO, self::AI_CONSTRUCTOR_USAGE, self::AR_TRY_ON, self::REVIEW,
            self::REGISTRATION, self::MIGRATION, self::CUSTOM, self::SURVEY,
            self::SOCIAL, self::ONBOARDING, self::FLOAT_YIELD => false,
        };
    }

    /**
     * Получить стандартный период hold в днях.
     */
    public function getHoldPeriodDays(): int
    {
        return match ($this) {
            self::REFERRAL => 14,
            self::FIRST_PURCHASE => 7,
            self::TURNOVER => 14,
            self::LOYALTY => 30,
            default => 0,
        };
    }

    /**
     * Получить стандартный период экспирации в днях.
     */
    public function getExpiryDays(): int
    {
        return match ($this) {
            self::PROMO => 30,
            self::REFERRAL => 365,
            self::FIRST_PURCHASE => 90,
            default => 365,
        };
    }

    /**
     * Проверить, доступен ли тип для B2B пользователей.
     * FLOAT_YIELD недоступен для вывода даже для B2B по законам РФ.
     */
    public function isAvailableForB2B(): bool
    {
        return match ($this) {
            self::TURNOVER, self::LOYALTY, self::CUSTOM => true,
            self::FLOAT_YIELD => false, // По законам РФ float yield - только бонусы, не вывод
            default => false,
        };
    }

    /**
     * Получить мультиplier для A/B-тестирования (по умолчанию 1.0).
     */
    public function getABTestMultiplier(): float
    {
        // В реальной системе это значение берётся из конфига A/B-теста
        return 1.0;
    }

    /**
     * Получить человекочитаемое название типа (для UI и логов).
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::REFERRAL => 'Реферальный бонус',
            self::FIRST_PURCHASE => 'Бонус за первую покупку',
            self::TURNOVER => 'Кэшбэк от оборота',
            self::LOYALTY => 'Бонус лояльности',
            self::PROMO => 'Промо-бонус',
            self::AI_CONSTRUCTOR_USAGE => 'Бонус за AI-конструктор',
            self::AR_TRY_ON => 'Бонус за AR-примерку',
            self::REVIEW => 'Бонус за отзыв',
            self::REGISTRATION => 'Бонус за регистрацию',
            self::MIGRATION => 'Бонус миграции',
            self::CUSTOM => 'Специальный бонус',
            self::SURVEY => 'Бонус за опрос',
            self::SOCIAL => 'Социальный бонус',
            self::ONBOARDING => 'Бонус за онбординг',
            self::FLOAT_YIELD => 'Доход от float',
        };
    }

    /**
     * Получить категорию для аналитики BigData.
     */
    public function getAnalyticsCategory(): string
    {
        return match ($this) {
            self::REFERRAL, self::SOCIAL => 'acquisition',
            self::FIRST_PURCHASE, self::REGISTRATION, self::ONBOARDING => 'onboarding',
            self::TURNOVER, self::LOYALTY => 'retention',
            self::PROMO, self::CUSTOM => 'marketing',
            self::AI_CONSTRUCTOR_USAGE, self::AR_TRY_ON => 'engagement',
            self::REVIEW, self::SURVEY => 'feedback',
            self::MIGRATION => 'system',
            self::FLOAT_YIELD => 'float_yield',
        };
    }
}
