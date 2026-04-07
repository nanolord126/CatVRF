<?php

declare(strict_types=1);

namespace App\Domains\GroceryAndDelivery\Integrations;

use Carbon\Carbon;
use Psr\Log\LoggerInterface;

/**
     * Оптимизация маршрутов доставки
     * Использует OSRM (Open Source Routing Machine) или Yandex.Maps API
     */
final readonly class RouteOptimizationService
{
        private const OSRM_ENDPOINT = 'http://router.project-osrm.org/route/v1';

        public function __construct(private readonly Factory $http,
        private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db) {}

        /**
         * Получить оптимальный маршрут для доставки
         */
        public function optimizeRoute(
            float $storeLat,
            float $storeLon,
            array $deliveries, // [{lat, lon, order_id}]
            string $correlationId,
        ): array {
            try {
                if (count($deliveries) === 0) {
                    return [
                        'success' => true,
                        'route' => [],
                        'distance' => 0,
                        'duration' => 0,
                    ];
                }

                // Формируем запрос к OSRM
                $coordinates = [[
                    'coordinates' => [$storeLon, $storeLat], // OSRM использует [lon, lat]
                    'name' => 'store',
                ]];

                foreach ($deliveries as $delivery) {
                    $coordinates[] = [
                        'coordinates' => [$delivery['lon'], $delivery['lat']],
                        'name' => 'delivery_' . $delivery['order_id'],
                    ];
                }

                // Вызываем OSRM для оптимизации маршрута
                $coordinateString = implode(';', array_map(
                    fn ($c) => "{$c['coordinates'][0]},{$c['coordinates'][1]}",
                    $coordinates
                ));

                $response = $this->http->timeout(30)->get(
                    self::OSRM_ENDPOINT . "/trip/v1/car/{$coordinateString}
