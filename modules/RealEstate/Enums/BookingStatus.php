<?php declare(strict_types=1);

namespace Modules\RealEstate\Enums;

enum BookingStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case EXPIRED = 'expired';
    case REFUNDED = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Ожидает подтверждения',
            self::CONFIRMED => 'Подтверждено',
            self::COMPLETED => 'Завершено',
            self::CANCELLED => 'Отменено',
            self::EXPIRED => 'Истекло',
            self::REFUNDED => 'Возвращено',
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return match ($this) {
            self::PENDING => in_array($status, [self::CONFIRMED, self::CANCELLED, self::EXPIRED], true),
            self::CONFIRMED => in_array($status, [self::COMPLETED, self::CANCELLED, self::REFUNDED], true),
            self::COMPLETED => false,
            self::CANCELLED => false,
            self::EXPIRED => false,
            self::REFUNDED => false,
        };
    }
}
