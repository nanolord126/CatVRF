<?php

declare(strict_types=1);

namespace App\Domains\GeoLogistics\Infrastructure\Services;

use App\Domains\GeoLogistics\Domain\Contracts\GeoRoutingServiceInterface;
use App\Domains\GeoLogistics\Domain\ValueObjects\Coordinates;

/**
 * OSRM-совместимая реализация маршрутизации.
 *
 * Текущая версия использует формулу гаверсинуса (Haversine) как
 * локальный stub.  В production среда 2026 вызывает OSRM / Yandex
 * Maps Routing API.  Логика расчёта инкапсулирована здесь, чтобы
 * замена бэкенда маршрутизации не затрагивала бизнес-слой.
 *
 * @see GeoRoutingServiceInterface  контракт, реализуемый данным сервисом
 * @package App\Domains\GeoLogistics\Infrastructure\Services
 */
final readonly class OsrmRoutingService implements GeoRoutingServiceInterface
{
    /** Радиус Земли в метрах. */
    private const EARTH_RADIUS_METERS = 6_371_000.0;

    /** Средняя скорость по городу: 30 км/ч ≈ 8.33 м/с. */
    private const DEFAULT_SPEED_MPS = 8.33;

    /** Множители скорости для разных режимов передвижения. */
    private const MODE_SPEED_MULTIPLIERS = [
        'driving'  => 1.0,
        'cycling'  => 0.5,
        'walking'  => 0.17,
    ];

    /**
     * Рассчитать маршрут между двумя точками с учётом способа передвижения.
     *
     * @param  Coordinates  $origin       Координаты отправления.
     * @param  Coordinates  $destination  Координаты назначения.
     * @param  string       $mode         Способ: driving | cycling | walking.
     * @return array{distance_meters: int, duration_seconds: int}
     *
     * @throws \InvalidArgumentException Если передан неизвестный mode.
     */
    public function calculateRouteMode(
        Coordinates $origin,
        Coordinates $destination,
        string $mode = 'driving',
    ): array {
        $speedMultiplier = self::MODE_SPEED_MULTIPLIERS[$mode]
            ?? throw new \InvalidArgumentException("Unknown travel mode: {$mode}");

        $distanceMeters = $this->haversineDistance($origin, $destination);
        $speedMps = self::DEFAULT_SPEED_MPS * $speedMultiplier;
        $durationSeconds = $speedMps > 0.0 ? (int) ($distanceMeters / $speedMps) : 0;

        return [
            'distance_meters'  => $distanceMeters,
            'duration_seconds' => $durationSeconds,
        ];
    }

    /**
     * Формула гаверсинуса — расстояние по дуге большого круга.
     *
     * @param  Coordinates  $a  Точка A.
     * @param  Coordinates  $b  Точка B.
     * @return int  Расстояние в метрах (округлено вниз).
     */
    private function haversineDistance(Coordinates $a, Coordinates $b): int
    {
        $latFrom = deg2rad($a->latitude);
        $lonFrom = deg2rad($a->longitude);
        $latTo = deg2rad($b->latitude);
        $lonTo = deg2rad($b->longitude);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $halfChordSquared = sin($latDelta / 2) ** 2
            + cos($latFrom) * cos($latTo) * sin($lonDelta / 2) ** 2;

        $centralAngle = 2.0 * asin(sqrt($halfChordSquared));

        return (int) ($centralAngle * self::EARTH_RADIUS_METERS);
    }
}
