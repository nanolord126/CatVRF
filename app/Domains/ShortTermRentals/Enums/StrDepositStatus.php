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

enum StrDepositStatus: string
{

    case PENDING = 'pending';
        case HELD = 'held';
        case RELEASED = 'released';
        case CHARGED = 'charged';

        public function label(): string
        {
            return match($this) {
                self::PENDING => 'Ожидает холда',
                self::HELD => 'Вхолдирован на карте гостя',
                self::RELEASED => 'Возвращен гостю',
                self::CHARGED => 'Списан в счет ущерба',
            };
        }
}
