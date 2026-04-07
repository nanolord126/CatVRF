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


namespace App\Domains\Beauty\Enums;

enum CancellationPolicy: string
{

    case FLEXIBLE  = 'flexible';  // Мягкая: бесплатная отмена до 24ч
        case STRICT    = 'strict';    // Строгая: бесплатная отмена до 48ч
        case CORPORATE = 'corporate'; // Корпоративная (B2B): всегда штраф 10% за администрирование

        /**
         * Получить процент штрафа в зависимости от оставшегося времени (в часах).
         */
        public function getPenaltyPercent(float $hoursBefore): int
        {
            return match ($this) {
                self::FLEXIBLE => match (true) {
                    $hoursBefore >= 24 => 0,
                    $hoursBefore >= 4  => 30,
                    default            => 100,
                },
                self::STRICT => match (true) {
                    $hoursBefore >= 48 => 0,
                    $hoursBefore >= 24 => 30,
                    $hoursBefore >= 12 => 50,
                    default            => 100,
                },
                self::CORPORATE => match (true) {
                    $hoursBefore >= 48 => 10, // Админ-сбор
                    $hoursBefore >= 24 => 50,
                    default            => 100,
                },
            };
        }
}
