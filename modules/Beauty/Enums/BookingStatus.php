<?php

namespace Modules\Beauty\Enums;

enum BookingStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case UNPAID = 'unpaid';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case NO_SHOW = 'no_show';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Ожидание',
            self::CONFIRMED => 'Подтверждено',
            self::UNPAID => 'Не оплачено',
            self::COMPLETED => 'Завершено',
            self::CANCELLED => 'Отменено',
            self::NO_SHOW => 'Не явилась',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PENDING => 'warning',
            self::CONFIRMED => 'success',
            self::UNPAID => 'danger',
            self::COMPLETED => 'success',
            self::CANCELLED => 'secondary',
            self::NO_SHOW => 'danger',
        };
    }
}
