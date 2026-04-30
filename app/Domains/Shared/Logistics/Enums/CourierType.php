<?php

declare(strict_types=1);

namespace App\Domains\Logistics\Enums;

/**
 * Courier Vehicle Types with production rules for CatVRF.
 *
 * Production Strategy (2026):
 * - Pedestrian: 1.5-3km radius, 8-10kg max, 30-45 min delivery
 * - Scooter/e-bike: 4-8km radius, 15-20kg max, 20-35 min delivery
 * - Car/Taxi: 8-20+km radius, 50-100+kg max, 40-90 min delivery
 */
enum CourierType: string
{
    public function getLabel(): string
    {
        return match ($this) {
            self::PEDESTRIAN => 'Пеший курьер',
            self::SCOOTER => 'Самокат',
            self::EBIKE => 'Электровелосипед',
            self::CAR => 'Автомобиль',
            self::TAXI => 'Такси-курьер',
        };
    }

    public function getMaxRadiusKm(): float
    {
        return match ($this) {
            self::PEDESTRIAN => 2.5,
            self::SCOOTER => 8.0,
            self::EBIKE => 10.0,
            self::CAR => 20.0,
            self::TAXI => 25.0,
        };
    }

    public function getMaxWeightKg(): float
    {
        return match ($this) {
            self::PEDESTRIAN => 10.0,
            self::SCOOTER => 15.0,
            self::EBIKE => 20.0,
            self::CAR => 100.0,
            self::TAXI => 100.0,
        };
    }

    public function getAvgSpeedKmh(): float
    {
        return match ($this) {
            self::PEDESTRIAN => 5.0,
            self::SCOOTER => 15.0,
            self::EBIKE => 20.0,
            self::CAR => 30.0,
            self::TAXI => 30.0,
        };
    }

    public function getMaxDeliveryTimeMin(): int
    {
        return match ($this) {
            self::PEDESTRIAN => 45,
            self::SCOOTER => 35,
            self::EBIKE => 30,
            self::CAR => 90,
            self::TAXI => 90,
        };
    }

    public function getBatteryThreshold(): ?int
    {
        return match ($this) {
            self::PEDESTRIAN => null,
            self::SCOOTER => 30,
            self::EBIKE => 30,
            self::CAR => null,
            self::TAXI => null,
        };
    }

    public function requiresBattery(): bool
    {
        return $this->getBatteryThreshold() !== null;
    }

    public function getRoutingMode(): string
    {
        return match ($this) {
            self::PEDESTRIAN => 'walking',
            self::SCOOTER => 'biking',
            self::EBIKE => 'biking',
            self::CAR => 'driving',
            self::TAXI => 'driving',
        };
    }

    public function getParkingTimeMin(): int
    {
        return match ($this) {
            self::PEDESTRIAN => 0,
            self::SCOOTER => 2,
            self::EBIKE => 2,
            self::CAR => 8,
            self::TAXI => 5,
        };
    }

    public function getCostMultiplier(): float
    {
        return match ($this) {
            self::PEDESTRIAN => 1.0,
            self::SCOOTER => 1.2,
            self::EBIKE => 1.3,
            self::CAR => 2.0,
            self::TAXI => 2.2,
        };
    }

    public function isElectric(): bool
    {
        return in_array($this, [self::SCOOTER, self::EBIKE], true);
    }

    public function isMotorized(): bool
    {
        return in_array($this, [self::SCOOTER, self::EBIKE, self::CAR, self::TAXI], true);
    }

    /**
     * Check if this courier type is suitable for given order constraints.
     */
    public function canHandleOrder(float $weightKg, float $distanceKm): bool
    {
        return $this->getMaxWeightKg() >= $weightKg
            && $this->getMaxRadiusKm() >= $distanceKm;
    }

    /**
     * Get type compatibility score (0-1) for order constraints.
     * Lower score = less suitable, higher score = more suitable.
     */
    public function getCompatibilityScore(float $weightKg, float $distanceKm): float
    {
        $weightRatio = min($weightKg / $this->getMaxWeightKg(), 1.0);
        $distanceRatio = min($distanceKm / $this->getMaxRadiusKm(), 1.0);

        // Ideal: order uses 40-80% of capacity
        $weightScore = 1.0 - abs($weightRatio - 0.6);
        $distanceScore = 1.0 - abs($distanceRatio - 0.5);

        return max(0.0, min(1.0, ($weightScore + $distanceScore) / 2));
    }
    case PEDESTRIAN = 'pedestrian';
    case SCOOTER = 'scooter';
    case EBIKE = 'ebike';
    case CAR = 'car';
    case TAXI = 'taxi';
}
