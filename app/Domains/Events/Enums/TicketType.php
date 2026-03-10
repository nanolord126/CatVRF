<?php

namespace App\Domains\Events\Enums;

enum TicketType: string
{
    case EARLY_BIRD = 'early_bird';
    case REGULAR = 'regular';
    case VIP = 'vip';
    case STANDARD = 'standard';
    case GROUP = 'group';

    public function label(): string
    {
        return match($this) {
            self::EARLY_BIRD => 'Ранняя продажа',
            self::REGULAR => 'Обычный',
            self::VIP => 'VIP',
            self::STANDARD => 'Стандартный',
            self::GROUP => 'Групповой',
        };
    }

    public function discountPercent(): int
    {
        return match($this) {
            self::EARLY_BIRD => 20,
            self::REGULAR => 10,
            self::VIP => 0,
            self::STANDARD => 5,
            self::GROUP => 15,
        };
    }
}
