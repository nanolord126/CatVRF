<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Domain\Entities;

final class LoyaltyTier
{
    public const BRONZE = 'bronze';
    public const SILVER = 'silver';
    public const GOLD = 'gold';
    public const PLATINUM = 'platinum';

    public static function all(): array
    {
        return [
            self::BRONZE,
            self::SILVER,
            self::GOLD,
            self::PLATINUM,
        ];
    }

    public static function isValid(string $tier): bool
    {
        return in_array($tier, self::all(), true);
    }

    public static function getLabel(string $tier): string
    {
        return match ($tier) {
            self::BRONZE => 'Бронза',
            self::SILVER => 'Серебро',
            self::GOLD => 'Золото',
            self::PLATINUM => 'Платина',
            default => 'Без уровня',
        };
    }

    public static function getMultiplier(string $tier): float
    {
        return match ($tier) {
            self::BRONZE => 1.0,
            self::SILVER => 1.2,
            self::GOLD => 1.5,
            self::PLATINUM => 2.0,
            default => 1.0,
        };
    }

    public static function getMinSpentForTier(string $tier): float
    {
        return match ($tier) {
            self::BRONZE => 0,
            self::SILVER => 10000,
            self::GOLD => 50000,
            self::PLATINUM => 150000,
            default => 0,
        };
    }

    public static function getNextTier(string $currentTier): ?string
    {
        return match ($currentTier) {
            self::BRONZE => self::SILVER,
            self::SILVER => self::GOLD,
            self::GOLD => self::PLATINUM,
            self::PLATINUM => null,
            default => self::BRONZE,
        };
    }
}
