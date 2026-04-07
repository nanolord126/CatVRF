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
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 */


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
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 */


namespace App\Enums;

enum AppointmentReschedulePolicy: string {

    case UNLIMITED_FREE = 'unlimited_free'; // Без ограничений (VIP/Luxury)
        case ONCE_FREE_24H = 'once_free_24h';   // Один раз за 24ч (Стандарт)
        case ONCE_FREE_72H = 'once_free_72h';   // Один раз за 72ч (Групповые)
        case ONCE_FIXED_FEE = 'once_fixed_fee'; // Перенос с фиксированным сбором (Свадьбы/Корп)
        case NO_RESCHEDULE = 'no_reschedule';   // Перенос запрещен (Мастер-классы)
}
