<?php

declare(strict_types=1);

namespace Modules\Flowers\Domain\Enums;

enum OrderStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case IN_ASSEMBLY = 'in_assembly';
    case ASSEMBLED = 'assembled';
    case QUALITY_CHECKED = 'quality_checked';
    case READY_FOR_DELIVERY = 'ready_for_delivery';
    case OUT_FOR_DELIVERY = 'out_for_delivery';
    case DELIVERED = 'delivered';
    case PICKED_UP = 'picked_up';
    case CANCELLED = 'cancelled';
    case REFUNDED = 'refunded';

    public function canTransitionTo(OrderStatus $target): bool
    {
        return match ($this) {
            self::PENDING => in_array($target, [self::CONFIRMED, self::CANCELLED]),
            self::CONFIRMED => in_array($target, [self::IN_ASSEMBLY, self::CANCELLED]),
            self::IN_ASSEMBLY => in_array($target, [self::ASSEMBLED, self::CANCELLED]),
            self::ASSEMBLED => in_array($target, [self::QUALITY_CHECKED, self::CANCELLED]),
            self::QUALITY_CHECKED => in_array($target, [self::READY_FOR_DELIVERY, self::CANCELLED]),
            self::READY_FOR_DELIVERY => in_array($target, [self::OUT_FOR_DELIVERY, self::PICKED_UP]),
            self::OUT_FOR_DELIVERY => in_array($target, [self::DELIVERED]),
            self::DELIVERED, self::PICKED_UP => false,
            self::CANCELLED => in_array($target, [self::REFUNDED]),
            self::REFUNDED => false,
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::DELIVERED, self::PICKED_UP, self::REFUNDED]);
    }

    public function isCancellable(): bool
    {
        return in_array($this, [self::PENDING, self::CONFIRMED]);
    }

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Ожидает подтверждения',
            self::CONFIRMED => 'Подтверждён',
            self::IN_ASSEMBLY => 'В сборке',
            self::ASSEMBLED => 'Собран',
            self::QUALITY_CHECKED => 'Проверен',
            self::READY_FOR_DELIVERY => 'Готов к доставке',
            self::OUT_FOR_DELIVERY => 'В пути',
            self::DELIVERED => 'Доставлен',
            self::PICKED_UP => 'Получен',
            self::CANCELLED => 'Отменён',
            self::REFUNDED => 'Возвращён',
        };
    }
}
