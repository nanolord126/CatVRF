<?php

declare(strict_types=1);

namespace App\Domains\Auto\Taxi\Domain\Services;

use App\Domains\Auto\Taxi\Domain\ValueObjects\Coordinate;

/**
 * Геологистический сервис для расчёта маршрутов такси.
 *
 * Использует формулу гаверсинуса для вычисления дистанции по прямой
 * между двумя точками.  В production-среде будет заменён на интеграцию
 * с OSRM / Yandex Maps Routing API.
 *
 * @see \App\Services\FraudControlService
 * @see \App\Services\AuditService
 * @package App\Domains\Auto\Taxi\Domain\Services
 */
final readonly class GeoLogisticsService
{
    /** Радиус Земли в метрах. */
    private const EARTH_RADIUS_METERS = 6_371_000.0;

    /** Средняя скорость в городе (м/с) ≈ 30 км/ч. */
    private const CITY_AVG_SPEED_MPS = 8.33;

    /**
     * Получить информацию о маршруте между двумя точками.
     *
     * @param  Coordinate  $start  Точка отправления.
     * @param  Coordinate  $end    Точка назначения.
     * @return array{distance: float, duration: float}  Дистанция в метрах и время в секундах.
     */
    public function getRouteInfo(Coordinate $start, Coordinate $end): array
    {
        $distance = $this->calculateHaversineDistance($start, $end);
        $duration = $distance / self::CITY_AVG_SPEED_MPS;

        return [
            'distance' => round($distance, 2),
            'duration' => round($duration, 2),
        ];
    }

    /**
     * Оценить время прибытия (ETA) в секундах.
     *
     * @param  Coordinate  $start  Текущая позиция.
     * @param  Coordinate  $end    Точка назначения.
     * @param  float  $speedMps    Текущая скорость курьера/водителя (м/с).
     * @return float  Прогнозируемое время прибытия в секундах.
     */
    public function estimateEta(Coordinate $start, Coordinate $end, float $speedMps = self::CITY_AVG_SPEED_MPS): float
    {
        $distance = $this->calculateHaversineDistance($start, $end);

        if ($speedMps <= 0.0) {
            return 0.0;
        }

        return round($distance / $speedMps, 2);
    }

    /**
     * Рассчитать расстояние по формуле гаверсинуса.
     *
     * @param  Coordinate  $start  Начальная координата.
     * @param  Coordinate  $end    Конечная координата.
     * @return float  Расстояние в метрах.
     */
    private function calculateHaversineDistance(Coordinate $start, Coordinate $end): float
    {
        $startArr = $start->toArray();
        $endArr = $end->toArray();

        $latFrom = deg2rad($startArr['latitude']);
        $lonFrom = deg2rad($startArr['longitude']);
        $latTo = deg2rad($endArr['latitude']);
        $lonTo = deg2rad($endArr['longitude']);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $halfChordSquared = sin($latDelta / 2) ** 2
            + cos($latFrom) * cos($latTo) * sin($lonDelta / 2) ** 2;

        $centralAngle = 2.0 * asin(sqrt($halfChordSquared));

        return $centralAngle * self::EARTH_RADIUS_METERS;
    }
}
