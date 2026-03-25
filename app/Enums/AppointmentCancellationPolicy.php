<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * AppointmentCancellationPolicy (Canon 2026)
 * Политики отмены бронирований.
 */
enum AppointmentCancellationPolicy: string
{
    case FREE = 'free';                 // Полностью бесплатная отмена
    case STANDARD = 'standard';         // Стандартная (48-72ч)
    case STRICT_30D = 'strict_30d';     // Строгая 30-дневная (Свадьбы/Корп)
    case STRICT_14D = 'strict_14d';     // Строгая 14-дневная (Фотосессии/Группы)
    case NON_REFUNDABLE = 'non_refundable'; // Без возврата (VIP/Спец-акции)
}
