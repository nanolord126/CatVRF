<?php

declare(strict_types=1);

namespace App\Domains\Logistics\Services;

use App\Domains\Logistics\Models\Courier;
use App\Domains\Logistics\Models\CourierTypeConfiguration;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\GeoLogistics\Services\GeoLogisticsService;
use Psr\Log\LoggerInterface;

/**
 * Multi-criteria filtering service for courier assignment.
 *
 * Production Strategy:
 * - Filter couriers by type-specific constraints (weight, radius, battery)
 * - Apply soft constraints with scoring (not hard cutoffs)
 * - Support weather penalties and time-of-day restrictions
 * - Respect preferred zones and operating hours
 */
final readonly class CourierAssignmentCriteriaService
{
    public function __construct(
        private readonly CourierTypeConfigurationService $configService,
        private readonly GeoLogisticsService $geoService,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Filter couriers by order constraints.
     *
     * @param  Collection<int, Courier>  $couriers
     * @param  array{lat: float, lng: float}  $pickupLocation
     * @param  array{lat: float, lng: float}  $deliveryLocation
     */
    public function filterByOrderConstraints(
        Collection $couriers,
        int $tenantId,
        float $weightKg,
        array $pickupLocation,
        array $deliveryLocation,
        ?string $city = null,
        array $weatherConditions = [],
    ): Collection {
        // Calculate route distance
        $route = $this->geoService->calculateRoute($pickupLocation, $deliveryLocation, 'driving');
        $distanceKm = $route['distance_km'];

        $this->logger->$this->logger->info('Filtering couriers by order constraints', [
            'tenant_id' => $tenantId,
            'weight_kg' => $weightKg,
            'distance_km' => $distanceKm,
            'courier_count' => $couriers->count(),
        ]);

        return $couriers->filter(function (Courier $courier) use (
            $tenantId,
            $weightKg,
            $distanceKm,
            $pickupLocation,
            $city,
            $weatherConditions,
        ) {
            // Get type configuration
            $type = $courier->getType();
            if ($type === null) {
                return false;
            }

            $config = $this->configService->getConfiguration($tenantId, $type->value, $city);
            if ($config === null) {
                return false;
            }

            // Hard constraint: availability
            if (! $courier->isAvailable()) {
                return false;
            }

            // Hard constraint: weight capacity
            if (! $courier->canHandleWeight($weightKg)) {
                return false;
            }

            // Hard constraint: distance radius (with weather penalty consideration)
            $weatherPenalty = $this->getWeatherPenalty($config, $weatherConditions);
            $effectiveRadius = $config->getEffectiveMaxRadiusKm($weatherPenalty);

            if (! $courier->canHandleDistance($distanceKm)) {
                return false;
            }

            // Hard constraint: battery for electric vehicles
            if (! $courier->hasSufficientBattery($config->battery_threshold)) {
                return false;
            }

            // Hard constraint: operating hours
            if (! $config->isWithinOperatingHours()) {
                return false;
            }

            // Hard constraint: preferred zones
            if (! $courier->isInPreferredZone($pickupLocation['lat'], $pickupLocation['lng'])) {
                return false;
            }

            return true;
        });
    }

    /**
     * Get soft constraint score for courier (0-1).
     * Higher score = better fit.
     */
    public function getSoftConstraintScore(
        Courier $courier,
        int $tenantId,
        float $weightKg,
        float $distanceKm,
        ?string $city = null,
        array $weatherConditions = [],
    ): float {
        $type = $courier->getType();
        if ($type === null) {
            return 0.0;
        }

        $config = $this->configService->getConfiguration($tenantId, $type->value, $city);
        if ($config === null) {
            return 0.0;
        }

        $score = 1.0;

        // Weight capacity utilization (ideal: 40-80%)
        $weightRatio = $weightKg / $courier->capacity_kg;
        if ($weightRatio < 0.4) {
            $score *= 0.9; // Underutilized
        } elseif ($weightRatio > 0.8) {
            $score *= 0.7; // Near max capacity
        }

        // Distance utilization (ideal: 30-70% of max radius)
        $distanceRatio = $distanceKm / $courier->getMaxRadiusKm();
        if ($distanceRatio < 0.3) {
            $score *= 0.85; // Too short
        } elseif ($distanceRatio > 0.7) {
            $score *= 0.6; // Too long
        }

        // Weather penalty
        $weatherPenalty = $this->getWeatherPenalty($config, $weatherConditions);
        $score *= (1.0 - $weatherPenalty);

        // Rating bonus
        if ($courier->rating >= 4.5) {
            $score *= 1.1;
        } elseif ($courier->rating < 4.0) {
            $score *= 0.9;
        }

        // Type priority (lower priority number = higher priority)
        $priorityScore = 1.0 - ($config->priority / 200.0); // Normalize to 0-1
        $score *= $priorityScore;

        return max(0.0, min(1.0, $score));
    }

    /**
     * Build query for available couriers with type filtering.
     */
    public function buildAvailableCouriersQuery(
        int $tenantId,
        ?string $type = null,
    ): Builder {
        $query = Courier::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->whereIn('status', [Courier::STATUS_ONLINE, Courier::STATUS_IDLE]);

        if ($type !== null) {
            $query->where('vehicle_type', $type);
        }

        return $query;
    }

    /**
     * Get weather penalty from configuration.
     */
    private function getWeatherPenalty(CourierTypeConfiguration $config, array $weatherConditions): float
    {
        if (empty($weatherConditions)) {
            return 0.0;
        }

        $maxPenalty = 0.0;

        foreach ($weatherConditions as $condition) {
            $penalty = $config->getWeatherPenalty($condition);
            $maxPenalty = max($maxPenalty, $penalty);
        }

        return $maxPenalty;
    }
}
