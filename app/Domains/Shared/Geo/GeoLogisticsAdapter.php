<?php

declare(strict_types=1);

namespace App\Domains\Shared\Geo;

use App\Domains\GeoLogistics\Services\GeoLogisticsService;
use App\Domains\GeoLogistics\Services\GeoLogisticsServiceInterface;
use App\Domains\GeoLogistics\Services\GeoLogisticsCoordinatorService;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * GeoLogistics Adapter - unified interface for all 28 verticals.
 *
 * Provides clean API for delivery calculations, route estimation,
 * and slot availability without exposing GeoLogistics domain internals.
 */
final readonly class GeoLogisticsAdapter
{
    public function __construct(
        private readonly GeoLogisticsServiceInterface $geoLogisticsService,
        private readonly GeoLogisticsCoordinatorService $coordinator,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Calculate delivery for order.
     *
     * @return array{cost: int, eta: int, distance: int, route_data: array}
     */
    public function calculateDeliveryForOrder(array $orderData): array
    {
        $correlationId = (string) Str::uuid();

        $from = $orderData['seller_address'] ?? '';
        $to = $orderData['buyer_address'] ?? '';

        if (empty($from) || empty($to)) {
            throw new \InvalidArgumentException('Seller and buyer addresses are required');
        }

        $routeResult = $this->geoLogisticsService->calculateRoute($from, $to, $correlationId);

        $this->logger->info('Delivery calculated via GeoLogistics adapter', [
            'vertical' => $orderData['vertical'] ?? 'unknown',
            'sub_vertical' => $orderData['sub_vertical'] ?? null,
            'correlation_id' => $correlationId,
            'eta' => $routeResult->estimated_time ?? null,
            'distance' => $routeResult->distance ?? null,
        ]);

        // Calculate cost based on distance and vertical-specific rules
        $cost = $this->calculateDeliveryCost($routeResult, $orderData);

        return [
            'cost' => $cost,
            'eta' => $routeResult->estimated_time ?? 30,
            'distance' => $routeResult->distance ?? 0,
            'route_data' => $routeResult->route_data ?? [],
        ];
    }

    /**
     * Get available delivery slots for address and vertical.
     *
     * @return array<int, array{time: string, available: bool}>
     */
    public function getAvailableSlots(string $address, string $vertical, ?string $subVertical = null): array
    {
        $correlationId = (string) Str::uuid();

        // Mock implementation - in real app would call slot service
        $baseSlots = $this->getBaseSlotsForVertical($vertical);

        $this->logger->info('Delivery slots retrieved via GeoLogistics adapter', [
            'vertical' => $vertical,
            'sub_vertical' => $subVertical,
            'address' => $address,
            'slots_count' => count($baseSlots),
            'correlation_id' => $correlationId,
        ]);

        return $baseSlots;
    }

    /**
     * Check if address is in delivery zone.
     */
    public function isAddressInDeliveryZone(string $address, string $vertical): bool
    {
        // Mock implementation - in real app would check against GeoZone
        $config = config('verticals.'.$vertical.'.geo', []);
        $defaultZone = $config['default_zone'] ?? 'city_center';

        return $defaultZone !== 'disabled';
    }

    /**
     * Get delivery cost estimate.
     */
    private function calculateDeliveryCost(object $routeResult, array $orderData): int
    {
        $vertical = $orderData['vertical'] ?? 'unknown';
        $distance = $routeResult->distance ?? 0;

        // Base cost per km
        $baseCostPerKm = match ($vertical) {
            'supermarket', 'food', 'confectionery' => 50, // cheaper for food
            'auto', 'taxi' => 100, // higher for auto
            'real_estate' => 200, // premium for real estate
            default => 80,
        };

        // Calculate cost (distance in meters to km)
        $distanceKm = max(1, $distance / 1000);
        $cost = (int) ($distanceKm * $baseCostPerKm);

        // Minimum cost
        $minCost = match ($vertical) {
            'supermarket', 'food' => 200,
            'auto', 'taxi' => 300,
            default => 250,
        };

        return max($minCost, $cost);
    }

    /**
     * Get base delivery slots for vertical.
     */
    private function getBaseSlotsForVertical(string $vertical): array
    {
        $now = now();

        return match ($vertical) {
            'supermarket', 'food', 'confectionery' => [
                // 20-minute windows for food
                ['time' => $now->copy()->addMinutes(30)->toTimeString(), 'available' => true],
                ['time' => $now->copy()->addMinutes(50)->toTimeString(), 'available' => true],
                ['time' => $now->copy()->addMinutes(70)->toTimeString(), 'available' => true],
                ['time' => $now->copy()->addMinutes(90)->toTimeString(), 'available' => true],
                ['time' => $now->copy()->addMinutes(110)->toTimeString(), 'available' => true],
            ],
            'auto', 'taxi' => [
                // 1-hour windows for auto
                ['time' => $now->copy()->addHour()->toTimeString(), 'available' => true],
                ['time' => $now->copy()->addHours(2)->toTimeString(), 'available' => true],
                ['time' => $now->copy()->addHours(3)->toTimeString(), 'available' => true],
            ],
            default => [
                // 2-hour windows default
                ['time' => $now->copy()->addHours(2)->toTimeString(), 'available' => true],
                ['time' => $now->copy()->addHours(4)->toTimeString(), 'available' => true],
                ['time' => $now->copy()->addHours(6)->toTimeString(), 'available' => true],
            ],
        };
    }
}
