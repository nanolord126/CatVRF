<?php

declare(strict_types=1);

/**
 *  — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/component
 */


namespace App\Domains\Beauty\Domain\Enums;

enum AppointmentStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case NO_SHOW = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Ожидает подтверждения',
            self::CONFIRMED => 'Подтверждена',
            self::COMPLETED => 'Завершена',
            self::CANCELLED => 'Отменена',
            self::NO_SHOW => 'Клиент не пришел',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::CONFIRMED => 'primary',
            self::COMPLETED => 'success',
            self::CANCELLED => 'danger',
            self::NO_SHOW => 'gray',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
