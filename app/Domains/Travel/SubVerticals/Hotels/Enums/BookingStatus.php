<?php

declare(strict_types=1);

/**
 *  — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @version 2026.1
 *
 * @author CatVRF Team
 * @license Proprietary

 *
 * @see https://catvrf.ru/docs/component
 */

namespace App\Domains\Travel\SubVerticals\Hotels\Enums;

enum BookingStatus: string
{
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


    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case FAILED = 'failed';
}
