<?php declare(strict_types=1);

namespace Modules\Fashion\Enums;

enum OrderStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case PROCESSING = 'processing';
    case SHIPPED = 'shipped';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';
    case REFUNDED = 'refunded';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Ожидает обработки',
            self::CONFIRMED => 'Подтвержден',
            self::PROCESSING => 'В обработке',
            self::SHIPPED => 'Отправлен',
            self::DELIVERED => 'Доставлен',
            self::CANCELLED => 'Отменен',
            self::REFUNDED => 'Возвращен',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PENDING => 'warning',
            self::CONFIRMED => 'info',
            self::PROCESSING => 'primary',
            self::SHIPPED => 'info',
            self::DELIVERED => 'success',
            self::CANCELLED => 'danger',
            self::REFUNDED => 'secondary',
        };
    }
}
