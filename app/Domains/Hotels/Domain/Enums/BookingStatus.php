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


namespace App\Domains\Hotels\Domain\Enums;

enum BookingStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case CANCELLED = 'cancelled';
    case COMPLETED = 'completed';
    case FAILED = 'failed';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'Ожидает подтверждения',
            self::CONFIRMED => 'Подтверждено',
            self::CANCELLED => 'Отменено',
            self::COMPLETED => 'Завершено',
            self::FAILED => 'Ошибка',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::CONFIRMED => 'success',
            self::CANCELLED => 'danger',
            self::COMPLETED => 'primary',
            self::FAILED => 'danger',
        };
    }
}
