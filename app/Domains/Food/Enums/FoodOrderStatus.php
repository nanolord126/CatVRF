<?php

namespace App\Domains\Food\Enums;

enum FoodOrderStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case PREPARING = 'preparing';
    case READY = 'ready';
    case OUT_FOR_DELIVERY = 'out_for_delivery';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';
    case FAILED = 'failed';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'В ожидании',
            self::CONFIRMED => 'Подтверждено',
            self::PREPARING => 'Готовится',
            self::READY => 'Готово',
            self::OUT_FOR_DELIVERY => 'Доставляется',
            self::DELIVERED => 'Доставлено',
            self::CANCELLED => 'Отменено',
            self::FAILED => 'Ошибка',
        };
    }

    public function canBeCancelled(): bool
    {
        return in_array($this, [self::PENDING, self::CONFIRMED]);
    }

    public function isCompleted(): bool
    {
        return in_array($this, [self::DELIVERED, self::CANCELLED, self::FAILED]);
    }
}
