<?php

namespace App\Domains\Sports\Enums;

enum MembershipTier: string
{
    case BASIC = 'basic';
    case STANDARD = 'standard';
    case PREMIUM = 'premium';
    case ELITE = 'elite';

    public function label(): string
    {
        return match($this) {
            self::BASIC => 'Базовое',
            self::STANDARD => 'Стандартное',
            self::PREMIUM => 'Премиум',
            self::ELITE => 'Элит',
        };
    }

    public function monthlyPrice(): float
    {
        return match($this) {
            self::BASIC => 29.99,
            self::STANDARD => 49.99,
            self::PREMIUM => 79.99,
            self::ELITE => 149.99,
        };
    }

    public function benefits(): array
    {
        return match($this) {
            self::BASIC => ['gym_access', 'lockers'],
            self::STANDARD => ['gym_access', 'lockers', 'group_classes', 'showers'],
            self::PREMIUM => ['gym_access', 'lockers', 'group_classes', 'showers', 'personal_trainer', 'sauna'],
            self::ELITE => ['gym_access', 'lockers', 'group_classes', 'showers', 'personal_trainer', 'sauna', 'spa', 'priority_booking'],
        };
    }
}
