<?php declare(strict_types=1);

namespace App\Domains\Hotels\Enums;

/**
 * КАНОН 2026: Booking Status Enum (Layer 6)
 */
enum BookingStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case FAILED = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Ожидает оплаты',
            self::CONFIRMED => 'Подтверждено',
            self::ACTIVE => 'Проживание',
            self::COMPLETED => 'Завершено',
            self::CANCELLED => 'Отменено',
            self::FAILED => 'Ошибка платежа',
        };
    }
}
