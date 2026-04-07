<?php declare(strict_types=1);

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


namespace App\Domains\ShortTermRentals\Enums;

enum StrBookingStatus: string
{

    case PENDING = 'pending';
        case CONFIRMED = 'confirmed';
        case ACTIVE = 'active';
        case COMPLETED = 'completed';
        case CANCELLED = 'cancelled';
        case FAILED = 'failed';

        public function label(): string
        {
            return match($this) {
                self::PENDING => 'Ожидание оплаты',
                self::CONFIRMED => 'Подтверждено (предоплата)',
                self::ACTIVE => 'Гость проживает',
                self::COMPLETED => 'Завершено',
                self::CANCELLED => 'Отменено',
                self::FAILED => 'Ошибка системы',
            };
        }

        public function color(): string
        {
            return match($this) {
                self::PENDING => 'warning',
                self::CONFIRMED => 'success',
                self::ACTIVE => 'info',
                self::COMPLETED => 'gray',
                self::CANCELLED, self::FAILED => 'danger',
            };
        }
}
