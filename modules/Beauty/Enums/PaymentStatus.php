<?php

namespace Modules\Beauty\Enums;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Ожидание платежа',
            self::CONFIRMED => 'Оплачено',
            self::FAILED => 'Ошибка платежа',
            self::REFUNDED => 'Возвращено',
            self::CANCELLED => 'Отменено',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PENDING => 'warning',
            self::CONFIRMED => 'success',
            self::FAILED => 'danger',
            self::REFUNDED => 'info',
            self::CANCELLED => 'secondary',
        };
    }
}
