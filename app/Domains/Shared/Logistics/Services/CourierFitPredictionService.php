<?php

declare(strict_types=1);

namespace App\Domains\Logistics\Services;

use App\Domains\Logistics\Models\Courier;
use Illuminate\Support\Collection;
use Psr\Log\LoggerInterface;

/**
 * Courier Fit Prediction Service for ETA and AI scoring.
 *
 * Production Strategy:
 * - Predict ETA using ML model or rule-based fallback
 * - Predict courier fit score for assignment
 * - Support real-time inference with circuit breaker
 * - Cache predictions for performance
 */
final readonly class CourierFitPredictionService
{
    public function __construct(
        private readonly CourierFitScoringService $scoringService,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Predict ETA for courier to complete delivery.
     *
     * @param  array{lat: float, lng: float}  $pickupLocation
     * @param  array{lat: float, lng: float}  $deliveryLocation
     */
    public function predictETA(
        Courier $courier,
        array $pickupLocation,
        array $deliveryLocation,
        float $weightKg,
        int $tenantId,
        ?string $city = null,
    ): int {
        $type = $courier->getType();

        if ($type === null) {
            return 45; // Default fallback
        }

        // Rule-based ETA calculation
        $distanceKm = $this->calculateDistance($pickupLocation, $deliveryLocation);
        $speedKmh = $type->getAvgSpeedKmh();
        $parkingTimeMin = $type->getParkingTimeMin();

        $travelTimeMin = ($distanceKm / $speedKmh) * 60;
        $pickupTimeMin = 10; // Fixed pickup time
        $deliveryTimeMin = 5; // Fixed delivery time

        $eta = (int) ceil($travelTimeMin + $pickupTimeMin + $deliveryTimeMin + $parkingTimeMin);

        // Apply weather penalty if available
        // TODO: Integrate with weather service

        $this->logger->$this->logger->info('ETA prediction', [
            'courier_id' => $courier->id,
            'type' => $type->value,
            'distance_km' => $distanceKm,
            'eta_min' => $eta,
        ]);

        return $eta;
    }

    /**
     * Predict courier fit score using ML or rules.
     */
    public function predictFitScore(
        Courier $courier,
        int $tenantId,
        float $weightKg,
        float $distanceKm,
        array $pickupLocation,
        array $deliveryLocation,
        ?string $city = null,
        array $weatherConditions = [],
    ): float {
        // Use rule-based scoring for now
        // TODO: Integrate with ML model (XGBoost/LightGBM) for production
        return $this->scoringService->calculateRuleBasedScore(
            $courier,
            $tenantId,
            $weightKg,
            $distanceKm,
            $city,
            $weatherConditions,
        );
    }

    /**
     * Batch predict ETA for multiple couriers.
     *
     * @param  Collection<int, Courier>  $couriers
     * @return Collection<int, array{courier_id: int, eta_min: int}>
     */
    public function batchPredictETA(
        Collection $couriers,
        array $pickupLocation,
        array $deliveryLocation,
        float $weightKg,
        int $tenantId,
        ?string $city = null,
    ): Collection {
        return $couriers->map(function (Courier $courier) use (
            $pickupLocation,
            $deliveryLocation,
            $weightKg,
            $tenantId,
            $city,
        ) {
            return [
                'courier_id' => $courier->id,
                'eta_min' => $this->predictETA(
                    $courier,
                    $pickupLocation,
                    $deliveryLocation,
                    $weightKg,
                    $tenantId,
                    $city,
                ),
            ];
        });
    }

    /**
     * Predict delivery success probability.
     */
    public function predictSuccessProbability(
        Courier $courier,
        float $distanceKm,
        float $weightKg,
    ): float {
        // Rule-based probability
        $baseProbability = 0.95;

        // Rating factor
        $ratingFactor = $courier->rating / 5.0;

        // Distance penalty (longer = lower probability)
        $distancePenalty = min(0.1, $distanceKm / 100);

        // Battery penalty for electric vehicles
        $batteryPenalty = 0;
        if ($courier->getType()?->requiresBattery() && ($courier->battery_level ?? 100) < 30) {
            $batteryPenalty = 0.15;
        }

        $probability = $baseProbability * $ratingFactor - $distancePenalty - $batteryPenalty;

        return max(0.5, min(0.99, $probability));
    }

    /**
     * Calculate distance between two points (Haversine formula).
     */
    private function calculateDistance(array $from, array $to): float
    {
        $lat1 = deg2rad($from['lat']);
        $lon1 = deg2rad($from['lng']);
        $lat2 = deg2rad($to['lat']);
        $lon2 = deg2rad($to['lng']);

        $dlat = $lat2 - $lat1;
        $dlon = $lon2 - $lon1;

        $a = sin($dlat / 2) ** 2 + cos($lat1) * cos($lat2) * sin($dlon / 2) ** 2;
        $c = 2 * asin(sqrt($a));

        return 6371 * $c; // Earth radius in km
    }
}
