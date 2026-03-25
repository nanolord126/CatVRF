<?php declare(strict_types=1);

namespace App\Domains\Beauty\Enums;

/**
 * Политики переноса бронирований в вертикали Beauty 2026.
 */
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
