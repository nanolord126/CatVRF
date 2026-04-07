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

enum ReschedulePolicy: string
{

    case STANDARD = 'standard';
        case PREMIUM  = 'premium';  // Гибкие условия за доп. плату при бронировании

        /**
         * Получить базовый процент комиссии за перенос.
         */
        public function getBaseFeePercent(float $hoursBefore): int
        {
            return match (true) {
                $hoursBefore >= 48 => 0,
                $hoursBefore >= 24 => 10,
                $hoursBefore >= 12 => 25,
                $hoursBefore >= 4  => 40,
                default            => 100, // Перенос невозможен (фактически отмена со 100% штрафом)
            };
        }
}
