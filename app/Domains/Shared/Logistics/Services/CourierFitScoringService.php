<?php

declare(strict_types=1);

namespace App\Domains\Logistics\Services;

use Carbon\CarbonImmutable;

use App\Domains\Logistics\Enums\CourierType;
use App\Domains\Logistics\Models\Courier;
use Illuminate\Support\Collection;

/**
 * Courier Fit Scoring Service with ML-ready features.
 *
 * Production Strategy:
 * - Extract features for ML model training/inference
 * - Support both rule-based and ML-based scoring
 * - Provide feature vectors for XGBoost/LightGBM models
 * - Include historical performance metrics
 */
final readonly class CourierFitScoringService
{
    public function __construct(
        private readonly CourierAssignmentCriteriaService $criteriaService,
    ) {}

    /**
     * Extract ML-ready features for courier-order fit scoring.
     *
     * @return array<string, float|int|string> Feature vector
     */
    public function extractFeatures(
        Courier $courier,
        int $tenantId,
        float $weightKg,
        float $distanceKm,
        array $pickupLocation,
        array $deliveryLocation,
        ?string $city = null,
        array $weatherConditions = [],
    ): array {
        $type = $courier->getType();
        $config = $this->criteriaService->configService->getConfiguration($tenantId, $type?->value ?? '', $city);

        return [
            // Courier type features
            'courier_type_pedestrian' => $type === CourierType::PEDESTRIAN ? 1 : 0,
            'courier_type_scooter' => $type === CourierType::SCOOTER ? 1 : 0,
            'courier_type_ebike' => $type === CourierType::EBIKE ? 1 : 0,
            'courier_type_car' => $type === CourierType::CAR ? 1 : 0,
            'courier_type_taxi' => $type === CourierType::TAXI ? 1 : 0,

            // Capacity features
            'weight_ratio' => $courier->capacity_kg > 0 ? $weightKg / $courier->capacity_kg : 0,
            'distance_ratio' => $courier->getMaxRadiusKm() > 0 ? $distanceKm / $courier->getMaxRadiusKm() : 0,
            'capacity_remaining_kg' => max(0, $courier->capacity_kg - $weightKg),
            'radius_remaining_km' => max(0, $courier->getMaxRadiusKm() - $distanceKm),

            // Battery features (for electric vehicles)
            'battery_level' => $courier->battery_level ?? 100,
            'battery_critical' => ($type?->requiresBattery() ?? false) && ($courier->battery_level ?? 100) < 30 ? 1 : 0,

            // Performance features
            'courier_rating' => $courier->rating,
            'delivery_count' => $courier->delivery_count,
            'is_verified' => $courier->is_verified ? 1 : 0,

            // Distance features
            'distance_km' => $distanceKm,
            'distance_bucket_short' => $distanceKm < 2 ? 1 : 0,
            'distance_bucket_medium' => $distanceKm >= 2 && $distanceKm < 8 ? 1 : 0,
            'distance_bucket_long' => $distanceKm >= 8 ? 1 : 0,

            // Weight features
            'weight_kg' => $weightKg,
            'weight_bucket_light' => $weightKg < 5 ? 1 : 0,
            'weight_bucket_medium' => $weightKg >= 5 && $weightKg < 15 ? 1 : 0,
            'weight_bucket_heavy' => $weightKg >= 15 ? 1 : 0,

            // Type compatibility score (from enum)
            'type_compatibility_score' => $type?->getCompatibilityScore($weightKg, $distanceKm) ?? 0,

            // Configuration features
            'priority' => $config?->priority ?? 100,
            'cost_multiplier' => $config?->cost_multiplier ?? 1.0,
            'max_delivery_time_min' => $config?->max_delivery_time_min ?? 60,

            // Weather features
            'weather_rain' => in_array('rain', $weatherConditions, true) ? 1 : 0,
            'weather_snow' => in_array('snow', $weatherConditions, true) ? 1 : 0,
            'weather_wind' => in_array('wind', $weatherConditions, true) ? 1 : 0,
            'weather_penalty' => $config ? $config->getWeatherPenalty('rain') : 0, // Example

            // Time features
            'hour_of_day' => (int) CarbonImmutable::now()->format('H'),
            'is_rush_hour' => $this->isRushHour() ? 1 : 0,
            'is_weekend' => CarbonImmutable::now()->isWeekend() ? 1 : 0,

            // Courier-specific features
            'is_taxi_driver' => $courier->is_taxi_driver ? 1 : 0,
            'has_preferred_zones' => $courier->preferred_zones !== null && ! empty($courier->preferred_zones) ? 1 : 0,

            // Resort/Spit zone features (Week 1)
            'is_resort_spit' => $this->isResortSpit($deliveryLocation['lat'], $deliveryLocation['lng'], $tenantId) ? 1 : 0,
            'max_interpoint_distance_km' => $this->getMaxBatchDistance($deliveryLocation['lat'], $deliveryLocation['lng'], $tenantId),
            'linear_density_score' => $this->getLinearDensityScore($deliveryLocation['lat'], $deliveryLocation['lng'], $tenantId),
            'deadhead_ratio_threshold' => $this->getDeadheadThreshold($deliveryLocation['lat'], $deliveryLocation['lng'], $tenantId),

            // Historical performance (if available from analytics)
            'historical_success_rate' => $this->getHistoricalSuccessRate($courier->id),
            'avg_delivery_time_min' => $this->getAverageDeliveryTime($courier->id),
        ];
    }

    /**
     * Calculate rule-based fit score (0-1).
     * Fallback when ML model is not available.
     */
    public function calculateRuleBasedScore(
        Courier $courier,
        int $tenantId,
        float $weightKg,
        float $distanceKm,
        ?string $city = null,
        array $weatherConditions = [],
    ): float {
        $softScore = $this->criteriaService->getSoftConstraintScore(
            $courier,
            $tenantId,
            $weightKg,
            $distanceKm,
            $city,
            $weatherConditions,
        );

        // Add type-specific adjustments
        $type = $courier->getType();
        $typeScore = match ($type) {
            CourierType::PEDESTRIAN => $distanceKm < 2 ? 1.0 : 0.5,
            CourierType::SCOOTER => $distanceKm < 5 ? 1.0 : 0.7,
            CourierType::EBIKE => $distanceKm < 8 ? 1.0 : 0.8,
            CourierType::CAR => $distanceKm >= 5 ? 1.0 : 0.6,
            CourierType::TAXI => $distanceKm >= 8 ? 1.0 : 0.7,
            default => 0.5,
        };

        return max(0.0, min(1.0, ($softScore + $typeScore) / 2));
    }

    /**
     * Batch extract features for multiple couriers.
     *
     * @param  Collection<int, Courier>  $couriers
     * @return Collection<int, array<string, float|int|string>>
     */
    public function batchExtractFeatures(
        Collection $couriers,
        int $tenantId,
        float $weightKg,
        float $distanceKm,
        array $pickupLocation,
        array $deliveryLocation,
        ?string $city = null,
        array $weatherConditions = [],
    ): Collection {
        return $couriers->map(function (Courier $courier) use (
            $tenantId,
            $weightKg,
            $distanceKm,
            $pickupLocation,
            $deliveryLocation,
            $city,
            $weatherConditions,
        ) {
            return [
                'courier_id' => $courier->id,
                'features' => $this->extractFeatures(
                    $courier,
                    $tenantId,
                    $weightKg,
                    $distanceKm,
                    $pickupLocation,
                    $deliveryLocation,
                    $city,
                    $weatherConditions,
                ),
            ];
        });
    }

    /**
     * Check if location is in resort/spit zone.
     * In production, query ClickHouse ch_resort_zones table.
     */
    private function isResortSpit(float $lat, float $lng, int $tenantId): bool
    {
        // TODO: Query ClickHouse ch_resort_zones table
        // SELECT is_resort_spit FROM ch_resort_zones WHERE tenant_id = ? AND pointInPolygon((?, ?), polygon_geojson)
        return false; // Placeholder
    }

    /**
     * Get max batch distance for zone (1.5km for resort/spit, 0.8-1.2km for city).
     * In production, query ClickHouse ch_resort_zones table.
     */
    private function getMaxBatchDistance(float $lat, float $lng, int $tenantId): float
    {
        // TODO: Query ClickHouse ch_resort_zones table
        // SELECT max_batch_distance_km FROM ch_resort_zones WHERE tenant_id = ? AND pointInPolygon((?, ?), polygon_geojson)
        return $this->isResortSpit($lat, $lng, $tenantId) ? 1.5 : 0.8;
    }

    /**
     * Get linear density score for zone (0-1, higher = more linear/spit-like).
     * In production, query ClickHouse ch_resort_zones table.
     */
    private function getLinearDensityScore(float $lat, float $lng, int $tenantId): float
    {
        // TODO: Query ClickHouse ch_resort_zones table
        // SELECT linear_density_score FROM ch_resort_zones WHERE tenant_id = ? AND pointInPolygon((?, ?), polygon_geojson)
        return 0.5; // Placeholder
    }

    /**
     * Get deadhead ratio threshold (0.12 for resort, 0.08 for city).
     * In production, query ClickHouse ch_resort_zones table.
     */
    private function getDeadheadThreshold(float $lat, float $lng, int $tenantId): float
    {
        // TODO: Query ClickHouse ch_resort_zones table
        // SELECT deadhead_ratio_threshold FROM ch_resort_zones WHERE tenant_id = ? AND pointInPolygon((?, ?), polygon_geojson)
        return $this->isResortSpit($lat, $lng, $tenantId) ? 0.12 : 0.08;
    }

    /**
     * Check if current time is rush hour.
     */
    private function isRushHour(): bool
    {
        $hour = (int) CarbonImmutable::now()->format('H');

        return in_array($hour, [8, 9, 18, 19, 20], true);
    }

    /**
     * Get historical success rate for courier (placeholder).
     * In production, query analytics/ClickHouse for actual metrics.
     */
    private function getHistoricalSuccessRate(int $courierId): float
    {
        // TODO: Query ClickHouse or analytics table for actual success rate
        return 0.95; // Default assumption
    }

    /**
     * Get average delivery time for courier (placeholder).
     * In production, query analytics/ClickHouse for actual metrics.
     */
    private function getAverageDeliveryTime(int $courierId): float
    {
        // TODO: Query ClickHouse or analytics table for actual delivery time
        return 35.0; // Default assumption in minutes
    }
}
