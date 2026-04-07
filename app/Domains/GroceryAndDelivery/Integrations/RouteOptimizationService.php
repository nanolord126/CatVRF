<?php

declare(strict_types=1);

namespace App\Domains\GroceryAndDelivery\Integrations;

use Illuminate\Http\Client\Factory;
use Psr\Log\LoggerInterface;

/**
 * Оптимизация маршрутов доставки.
 * Использует OSRM (Open Source Routing Machine) или Yandex.Maps API.
 */
final readonly class RouteOptimizationService
{
    private const OSRM_ENDPOINT = 'http://router.project-osrm.org/route/v1';

    public function __construct(
        private Factory $http,
        private FraudControlService $fraud,
        private \Illuminate\Database\DatabaseManager $db,
        private LoggerInterface $logger,
    ) {}

    /**
     * Получить оптимальный маршрут для доставки.
     *
     * @param  float  $storeLat    Широта магазина
     * @param  float  $storeLon    Долгота магазина
     * @param  array  $deliveries  Список точек доставки [{lat, lon, order_id}]
     * @param  string $correlationId
     * @return array  Маршрут с дистанцией и временем
     */
    public function optimizeRoute(
        float $storeLat,
        float $storeLon,
        array $deliveries,
        string $correlationId,
    ): array {
        if (count($deliveries) === 0) {
            return [
                'success' => true,
                'route' => [],
                'distance' => 0,
                'duration' => 0,
            ];
        }

        try {
            $coordinates = ["{$storeLon},{$storeLat}"];

            foreach ($deliveries as $delivery) {
                $coordinates[] = "{$delivery['lon']},{$delivery['lat']}";
            }

            $coordinateString = implode(';', $coordinates);

            $response = $this->http->timeout(30)->get(
                self::OSRM_ENDPOINT . "/driving/{$coordinateString}",
                [
                    'overview' => 'full',
                    'steps' => 'true',
                    'geometries' => 'geojson',
                ]
            );

            if (!$response->successful()) {
                throw new \RuntimeException("OSRM request failed: {$response->status()}");
            }

            $data = $response->json();
            $route = $data['routes'][0] ?? [];

            $this->logger->info('RouteOptimization: route calculated', [
                'deliveries_count' => count($deliveries),
                'distance_meters' => $route['distance'] ?? 0,
                'duration_seconds' => $route['duration'] ?? 0,
                'correlation_id' => $correlationId,
            ]);

            return [
                'success' => true,
                'route' => $route,
                'distance' => $route['distance'] ?? 0,
                'duration' => $route['duration'] ?? 0,
                'waypoints' => $data['waypoints'] ?? [],
            ];
        } catch (\Throwable $e) {
            $this->logger->error('RouteOptimization: failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}