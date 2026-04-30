<?php

declare(strict_types=1);

namespace App\Domains\Bonuses\Enums;

/**
 * Уровни бонусной программы (Tier).
 *
 * Определяет привилегии и множители для разных сегментов пользователей.
 * Используется для gamification и retention.
 */
enum BonusTier: string
{
    /** Базовый уровень (новые пользователи). */
    case BRONZE = 'bronze';

    /** Средний уровень (активные пользователи). */
    case SILVER = 'silver';

    /** Высокий уровень (VIP пользователи). */
    case GOLD = 'gold';

    /** Максимальный уровень (топ-клиенты). */
    case PLATINUM = 'platinum';

    /**
     * Получить минимальный оборот для достижения tier (в копейках).
     */
    public function getMinTurnover(): int
    {
        return match ($this) {
            self::BRONZE => 0,
            self::SILVER => 10_000_00, // 10,000 RUB
            self::GOLD => 50_000_00, // 50,000 RUB
            self::PLATINUM => 200_000_00, // 200,000 RUB
        };
    }

    /**
     * Получить множитель для начисления бонусов.
     */
    public function getMultiplier(): float
    {
        return match ($this) {
            self::BRONZE => 1.0,
            self::SILVER => 1.2,
            self::GOLD => 1.5,
            self::PLATINUM => 2.0,
        };
    }

    /**
     * Получить максимальный процент оплаты бонусами от заказа.
     */
    public function getMaxSpendPercentage(): int
    {
        return match ($this) {
            self::BRONZE => 30,
            self::SILVER => 40,
            self::GOLD => 50,
            self::PLATINUM => 70,
        };
    }

    /**
     * Проверить, может ли пользователь выводить бонусы в реальные деньги.
     */
    public function canWithdrawToRealMoney(): bool
    {
        return match ($this) {
            self::GOLD, self::PLATINUM => true,
            self::BRONZE, self::SILVER => false,
        };
    }

    /**
     * Получить комиссию за вывод бонусов (в процентах).
     */
    public function getWithdrawalCommission(): float
    {
        return match ($this) {
            self::GOLD => 5.0,
            self::PLATINUM => 0.0,
            self::BRONZE, self::SILVER => 10.0,
        };
    }

    /**
     * Получить минимальную сумму для вывода (в копейках).
     */
    public function getMinWithdrawalAmount(): int
    {
        return match ($this) {
            self::GOLD => 5_000_00, // 5,000 RUB
            self::PLATINUM => 1_000_00, // 1,000 RUB
            self::BRONZE, self::SILVER => 10_000_00, // 10,000 RUB
        };
    }

    /**
     * Получить следующий tier.
     */
    public function getNextTier(): ?self
    {
        return match ($this) {
            self::BRONZE => self::SILVER,
            self::SILVER => self::GOLD,
            self::GOLD => self::PLATINUM,
            self::PLATINUM => null,
        };
    }

    /**
     * Получить предыдущий tier.
     */
    public function getPreviousTier(): ?self
    {
        return match ($this) {
            self::PLATINUM => self::GOLD,
            self::GOLD => self::SILVER,
            self::SILVER => self::BRONZE,
            self::BRONZE => null,
        };
    }

    /**
     * Получить человекочитаемое название (для UI).
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::BRONZE => 'Бронза',
            self::SILVER => 'Серебро',
            self::GOLD => 'Золото',
            self::PLATINUM => 'Платина',
        };
    }

    /**
     * Получить цвет для UI (Filament/Blade).
     */
    public function getColor(): string
    {
        return match ($this) {
            self::BRONZE => '#cd7f32',
            self::SILVER => '#c0c0c0',
            self::GOLD => '#ffd700',
            self::PLATINUM => '#e5e4e2',
        };
    }

    /**
     * Определить tier на основе оборота пользователя.
     */
    public static function fromTurnover(int $turnoverCents): self
    {
        return match (true) {
            $turnoverCents >= self::PLATINUM->getMinTurnover() => self::PLATINUM,
            $turnoverCents >= self::GOLD->getMinTurnover() => self::GOLD,
            $turnoverCents >= self::SILVER->getMinTurnover() => self::SILVER,
            default => self::BRONZE,
        };
    }
}
