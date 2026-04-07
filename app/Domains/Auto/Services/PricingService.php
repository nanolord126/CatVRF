<?php declare(strict_types=1);

namespace App\Domains\Auto\Services;


use Psr\Log\LoggerInterface;
final readonly class PricingService
{
    public function __construct(
        private readonly LoggerInterface $logger) {}


    /**
         * Расчет стоимости такси с учетом времени суток, спроса (Surge) и класса авто.
         */
        public function calculateTaxiRide(float $distanceKm, string $carClass, float $surgeMultiplier, string $correlationId): array
        {
            $basePrice = 10000; // 100 руб
            $perKmPrice = 2500;  // 25 руб/км

            $classMultiplier = match ($carClass) {
                'business' => 2.5,
                default => 1.0,
            };

            $totalKopecks = (int) (($basePrice + ($distanceKm * $perKmPrice)) * $classMultiplier * $surgeMultiplier);

            $this->logger->info('Taxi ride estimate', [
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

            $this->logger->info('Repair basic estimate', [
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
                'external' => 30000,
                'full' => 75000,
                'complex' => 120000,
                default => 30000,
            };

            $multiplier = match ($vehicleType) {
                'truck' => 2.0,
                'bus' => 3.0,
                default => 1.0,
            };

            $total = (int) ($base * $multiplier);

            $this->logger->info('Wash cost calculation', [
                'type' => $washType,
                'vehicle' => $vehicleType,
                'total_kopecks' => $total,
                'correlation_id' => $correlationId,
            ]);

            return $total;
        }
}
