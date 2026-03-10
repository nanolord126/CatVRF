<?php

namespace App\Domains\Hotel\Enums;

enum BookingStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case CHECKED_IN = 'checked_in';
    case CHECKED_OUT = 'checked_out';
    case CANCELLED = 'cancelled';
    case NO_SHOW = 'no_show';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'На рассмотрении',
            self::CONFIRMED => 'Подтверждено',
            self::CHECKED_IN => 'Заселен',
            self::CHECKED_OUT => 'Выселен',
            self::CANCELLED => 'Отменено',
            self::NO_SHOW => 'Не прибыл',
        };
    }

    public function canBeCancelled(): bool
    {
        return in_array($this, [self::PENDING, self::CONFIRMED]);
    }

    public function color(): string
    {
        return match($this) {
            self::PENDING => 'warning',
            self::CONFIRMED => 'info',
            self::CHECKED_IN => 'success',
            self::CHECKED_OUT => 'dark',
            self::CANCELLED => 'danger',
            self::NO_SHOW => 'secondary',
        };
    }
}
