<?php

declare(strict_types=1);

namespace Modules\Hotels\Domain\Enums;

enum GuestLoyaltyLevel: string
{
    case BRONZE = 'bronze';
    case SILVER = 'silver';
    case GOLD = 'gold';
    case PLATINUM = 'platinum';
    case CORPORATE = 'corporate';

    public function getLabel(): string
    {
        return match ($this) {
            self::BRONZE => 'Бронза',
            self::SILVER => 'Серебро',
            self::GOLD => 'Золото',
            self::PLATINUM => 'Платина',
            self::CORPORATE => 'Корпоративный',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::BRONZE => '#cd7f32',
            self::SILVER => '#c0c0c0',
            self::GOLD => '#ffd700',
            self::PLATINUM => '#e5e4e2',
            self::CORPORATE => '#1e40af',
        };
    }

    public function getDiscountPercent(): int
    {
        return match ($this) {
            self::BRONZE => 5,
            self::SILVER => 10,
            self::GOLD => 15,
            self::PLATINUM => 20,
            self::CORPORATE => 25,
        };
    }

    public function getRequiredNights(): int
    {
        return match ($this) {
            self::BRONZE => 0,
            self::SILVER => 5,
            self::GOLD => 15,
            self::PLATINUM => 30,
            self::CORPORATE => 0,
        };
    }
}
