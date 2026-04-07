<?php

declare(strict_types=1);

namespace App\Domains\Auto\Taxi\Domain\Services;

use App\Domains\Auto\Taxi\Domain\ValueObjects\Coordinate;

/**
 * Class PricingService
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
 * @package App\Domains\Auto\Taxi\Domain\Services
 */
final readonly class PricingService
{
    private const BASE_FARE = 150; // 150 RUB
    private const PER_KILOMETER_RATE = 25; // 25 RUB per km
    private const PER_MINUTE_RATE = 5; // 5 RUB per minute

    public function __construct(private readonly GeoLogisticsService $geoLogisticsService)
    {

    }

    /**
     * Calculate the estimated price for a ride.
     *
     * @param Coordinate $pickup
     * @param Coordinate $dropoff
     * @return int Price in cents
     */
    public function calculateEstimatedPrice(Coordinate $pickup, Coordinate $dropoff): int
    {
        $routeInfo = $this->geoLogisticsService->getRouteInfo($pickup, $dropoff);

        $distanceInKm = $routeInfo['distance'] / 1000;
        $durationInMinutes = $routeInfo['duration'] / 60;

        $price = self::BASE_FARE + ($distanceInKm * self::PER_KILOMETER_RATE) + ($durationInMinutes * self::PER_MINUTE_RATE);

        // Apply surge pricing if applicable
        $surgeMultiplier = $this->getSurgeMultiplier($pickup);
        $price *= $surgeMultiplier;

        return (int) ($price * 100);
    }

    private function getSurgeMultiplier(Coordinate $coordinate): float
    {
        // In a real application, this would check a surge pricing service
        // based on demand in the area of the coordinate.
        // For this example, we'll use a simple time-based surge.
        $hour = (int) date('H');
        if ($hour >= 7 && $hour <= 9 || $hour >= 17 && $hour <= 19) {
            return 1.5; // 1.5x surge during peak hours
        }

        return 1.0;
    }
}
