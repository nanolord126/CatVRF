<?php

namespace App\Domains\Delivery\Enums;

enum DeliveryStatus: string
{
    case PENDING = 'pending';
    case ACCEPTED = 'accepted';
    case PICKED_UP = 'picked_up';
    case IN_TRANSIT = 'in_transit';
    case DELIVERED = 'delivered';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'В ожидании',
            self::ACCEPTED => 'Принято',
            self::PICKED_UP => 'Забрано',
            self::IN_TRANSIT => 'В пути',
            self::DELIVERED => 'Доставлено',
            self::FAILED => 'Не доставлено',
            self::CANCELLED => 'Отменено',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [self::ACCEPTED, self::PICKED_UP, self::IN_TRANSIT]);
    }
}
