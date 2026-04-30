<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Domain\Entities;

final class AppointmentStatus
{
    public const PENDING = 'pending';
    public const CONFIRMED = 'confirmed';
    public const IN_PROGRESS = 'in_progress';
    public const COMPLETED = 'completed';
    public const CANCELLED = 'cancelled';
    public const NO_SHOW = 'no_show';
    public const PAID = 'paid';

    public static function all(): array
    {
        return [
            self::PENDING,
            self::CONFIRMED,
            self::IN_PROGRESS,
            self::COMPLETED,
            self::CANCELLED,
            self::NO_SHOW,
            self::PAID,
        ];
    }

    public static function isValid(string $status): bool
    {
        return in_array($status, self::all(), true);
    }

    public static function getLabel(string $status): string
    {
        return match ($status) {
            self::PENDING => 'Ожидает подтверждения',
            self::CONFIRMED => 'Подтверждена',
            self::IN_PROGRESS => 'В процессе',
            self::COMPLETED => 'Завершена',
            self::CANCELLED => 'Отменена',
            self::NO_SHOW => 'Неявка',
            self::PAID => 'Оплачена',
            default => 'Неизвестно',
        };
    }

    public static function getColor(string $status): string
    {
        return match ($status) {
            self::PENDING => 'yellow',
            self::CONFIRMED => 'blue',
            self::IN_PROGRESS => 'orange',
            self::COMPLETED => 'green',
            self::CANCELLED => 'red',
            self::NO_SHOW => 'red',
            self::PAID => 'green',
            default => 'gray',
        };
    }
}
