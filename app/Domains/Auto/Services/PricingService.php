<?php

declare(strict_types=1);

namespace App\Domains\Auto\Services;

use App\Domains\Auto\Models\Vehicle;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * КАНОН 2026: PricingService.
 * Расчет стоимостей для вертикали Auto.
 */
final readonly class PricingService
{
    /**
     * Расчет стоимости такси с учетом времени суток, спроса (Surge) и класса авто.
     */
    public function calculateTaxiRide(float $distanceKm, string $carClass, float $surgeMultiplier, string $correlationId): array
    {
        $basePrice = 10000; // 100 руб
        $perKmPrice = 2500;  // 25 руб/км
        
        $classMultiplier = match ($carClass) {
            'comfort' => 1.5,
            'business' => 2.5,
            default => 1.0,
        };

        $totalKopecks = (int) (($basePrice + ($distanceKm * $perKmPrice)) * $classMultiplier * $surgeMultiplier);

        Log::channel('audit')->info('Taxi ride estimate', [
            'distance_km' => $distanceKm,
            'class' => $carClass,
            'surge' => $surgeMultiplier,
            'total_kopecks' => $totalKopecks,
            'correlation_id' => $correlationId,
        ]);

        return [
            'base' => $basePrice,
            'km' => $perKmPrice,
            'distance' => $distanceKm,
            'class_multi' => $classMultiplier,
            'surge_multi' => $surgeMultiplier,
            'total_kopecks' => $totalKopecks,
        ];
    }

    /**
     * Предварительная стоимость ремонта (без AI Vision).
     */
    public function estimateRepairCost(array $tasks, string $correlationId): int
    {
        $total = 0;
        foreach ($tasks as $task) {
            // $task['work_hour_estimate'] * 1500 руб/час
            $total += ($task['work_hour_estimate'] ?? 1) * 150000; 
        }

        Log::channel('audit')->info('Repair basic estimate', [
            'tasks_count' => count($tasks),
            'total_kopecks' => $total,
            'correlation_id' => $correlationId,
        ]);

        return $total;
    }

    /**
     * Расчет стоимости автомойки.
     */
    public function calculateWashCost(string $washType, string $vehicleType, string $correlationId): int
    {
        $base = match ($washType) {
            'internal' => 50000,
            'external' => 30000,
            'full' => 75000,
            'complex' => 120000,
            default => 30000,
        };

        $multiplier = match ($vehicleType) {
            'suv' => 1.25,
            'truck' => 2.0,
            'bus' => 3.0,
            default => 1.0,
        };

        $total = (int) ($base * $multiplier);

        Log::channel('audit')->info('Wash cost calculation', [
            'type' => $washType,
            'vehicle' => $vehicleType,
            'total_kopecks' => $total,
            'correlation_id' => $correlationId,
        ]);

        return $total;
    }
}
