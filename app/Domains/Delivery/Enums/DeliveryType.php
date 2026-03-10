<?php

namespace App\Domains\Delivery\Enums;

enum DeliveryType: string
{
    case STANDARD = 'standard';
    case EXPRESS = 'express';
    case OVERNIGHT = 'overnight';
    case SCHEDULED = 'scheduled';

    public function label(): string
    {
        return match($this) {
            self::STANDARD => 'Стандартная',
            self::EXPRESS => 'Экспресс',
            self::OVERNIGHT => 'Ночная',
            self::SCHEDULED => 'По расписанию',
        };
    }

    public function estimatedDays(): int
    {
        return match($this) {
            self::STANDARD => 3,
            self::EXPRESS => 1,
            self::OVERNIGHT => 1,
            self::SCHEDULED => 5,
        };
    }
}
