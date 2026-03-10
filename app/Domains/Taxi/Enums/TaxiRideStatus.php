<?php

namespace App\Domains\Taxi\Enums;

enum TaxiRideStatus: string
{
    case REQUESTED = 'requested';
    case ACCEPTED = 'accepted';
    case IN_TRANSIT = 'in_transit';
    case ARRIVED = 'arrived';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::REQUESTED => 'Заказ принят',
            self::ACCEPTED => 'Водитель подтвердил',
            self::IN_TRANSIT => 'В пути',
            self::ARRIVED => 'Водитель прибыл',
            self::COMPLETED => 'Завершен',
            self::CANCELLED => 'Отменен',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::REQUESTED => 'info',
            self::ACCEPTED => 'primary',
            self::IN_TRANSIT => 'warning',
            self::ARRIVED => 'success',
            self::COMPLETED => 'dark',
            self::CANCELLED => 'danger',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [self::ACCEPTED, self::IN_TRANSIT, self::ARRIVED]);
    }
}
