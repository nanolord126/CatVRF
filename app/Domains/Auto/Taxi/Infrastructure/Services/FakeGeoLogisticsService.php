<?php

declare(strict_types=1);

namespace App\Domains\Auto\Taxi\Infrastructure\Services;

use App\Domains\Auto\Taxi\Domain\Services\GeoLogisticsServiceInterface;
use App\Domains\Auto\Taxi\Domain\ValueObjects\Coordinate;

/**
 * Class FakeGeoLogisticsService
 *
 * Part of the Auto vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Service layer following CatVRF canon:
 * - Constructor injection only (no Facades)
 * - FraudControlService::check() before mutations
 * - $this->db->transaction() wrapping all write operations
 * - Audit logging with correlation_id
 * - Tenant and BusinessGroup scoping
 *
 * @see \App\Services\FraudControlService
 * @see \App\Services\AuditService
 * @package App\Domains\Auto\Taxi\Infrastructure\Services
 */
final readonly class FakeGeoLogisticsService implements GeoLogisticsServiceInterface
{
    private const BASE_PRICE_PER_KM = 35;
    private const EARTH_RADIUS_KM = 6371.0;

    public function calculatePrice(Coordinate $from, Coordinate $to): int
    {
        $distance = $this->haversine($from, $to);
        $price = (int) ceil($distance * self::BASE_PRICE_PER_KM * 100);

        return max($price, 30000); // Минимальная цена 300 руб в копейках
    }

    public function estimateDuration(Coordinate $from, Coordinate $to): int
    {
        $distance = $this->haversine($from, $to);
        // Средняя скорость 30 км/ч в городе
        return (int) ceil(($distance / 30) * 60);
    }

    public function calculateRoute(Coordinate $from, Coordinate $to): array
    {
        return [
            'start' => ['lat' => $from->latitude, 'lon' => $from->longitude],
            'end' => ['lat' => $to->latitude, 'lon' => $to->longitude],
            'distance_km' => round($this->haversine($from, $to), 2),
            'duration_minutes' => $this->estimateDuration($from, $to),
        ];
    }

    private function haversine(Coordinate $from, Coordinate $to): float
    {
        $latFrom = deg2rad($from->latitude);
        $latTo = deg2rad($to->latitude);
        $deltaLat = deg2rad($to->latitude - $from->latitude);
        $deltaLon = deg2rad($to->longitude - $from->longitude);

        $a = sin($deltaLat / 2) ** 2
            + cos($latFrom) * cos($latTo) * sin($deltaLon / 2) ** 2;

        return 2 * self::EARTH_RADIUS_KM * asin(sqrt($a));
    }
}
