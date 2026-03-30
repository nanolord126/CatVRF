<?php declare(strict_types=1);

namespace App\Domains\GroceryAndDelivery\Integrations;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PartnerStoreAPIIntegration extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    private const PROVIDER_ENDPOINTS = [
            'magnit' => 'https://api.magnit.com/v1',
            'pyaterochka' => 'https://api.pyaterochka.com/v1',
            'vkusvill' => 'https://api.vkusvill.com/v1',
        ];

        public function __construct(
            private readonly Factory $http,
        ) {}

        /**
         * Синхронизировать товары и остатки из внешнего магазина
         */
        public function syncInventory(
            GroceryStore $store,
            string $correlationId,
        ): array {
            try {
                if (!$store->api_provider || !$store->api_token) {
                    throw new \Exception('Store API credentials not configured');
                }

                $endpoint = self::PROVIDER_ENDPOINTS[$store->api_provider] ?? null;
                if (!$endpoint) {
                    throw new \Exception("Unsupported API provider: {$store->api_provider}");
                }

                // Получаем каталог и остатки
                $response = $this->http->withToken($store->api_token)
                    ->timeout(30)
                    ->get("{$endpoint}/catalog/products", [
                        'store_id' => $store->id,
                        'limit' => 1000,
                    ]);

                if (!$response->successful()) {
                    throw new \Exception("API call failed: {$response->status()}");
                }

                $products = $response->json('data', []);
                $syncedCount = 0;
                $errors = [];

                DB::transaction(function () use ($store, $products, &$syncedCount, &$errors, $correlationId) {
                    foreach ($products as $productData) {
                        try {
                            $product = GroceryProduct::firstOrCreate(
                                [
                                    'store_id' => $store->id,
                                    'sku' => $productData['sku'] ?? null,
                                ],
                                [
                                    'name' => $productData['name'],
                                    'category' => $productData['category'],
                                    'price' => (int)($productData['price'] * 100), // Convert to kopecks
                                    'current_stock' => (int)$productData['stock'],
                                    'min_stock' => (int)($productData['min_stock'] ?? 5),
                                    'max_stock' => (int)($productData['max_stock'] ?? 100),
                                    'barcode' => $productData['barcode'] ?? null,
                                    'weight_kg' => (float)($productData['weight_kg'] ?? 0),
                                    'is_active' => (bool)$productData['is_active'],
                                    'correlation_id' => $correlationId,
                                ]
                            );

                            // Обновляем остатки и цену
                            $product->update([
                                'current_stock' => (int)$productData['stock'],
                                'price' => (int)($productData['price'] * 100),
                            ]);

                            $syncedCount++;
                        } catch (Throwable $e) {
                            $errors[] = [
                                'sku' => $productData['sku'] ?? 'unknown',
                                'error' => $e->getMessage(),
                            ];
                        }
                    }
                });

                Log::channel('audit')->info('Store inventory synced', [
                    'store_id' => $store->id,
                    'provider' => $store->api_provider,
                    'synced_count' => $syncedCount,
                    'error_count' => count($errors),
                    'correlation_id' => $correlationId,
                ]);

                return [
                    'success' => true,
                    'synced_count' => $syncedCount,
                    'error_count' => count($errors),
                    'errors' => $errors,
                ];
            } catch (Throwable $e) {
                Log::channel('audit')->error('Inventory sync failed', [
                    'store_id' => $store->id,
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);

                return [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        /**
         * Получить доступные слоты доставки из партнёра
         */
        public function getAvailableDeliverySlots(
            GroceryStore $store,
            string $correlationId,
        ): array {
            try {
                $endpoint = self::PROVIDER_ENDPOINTS[$store->api_provider] ?? null;
                if (!$endpoint) {
                    throw new \Exception("Unsupported API provider: {$store->api_provider}");
                }

                $response = $this->http->withToken($store->api_token)
                    ->timeout(15)
                    ->get("{$endpoint}/delivery/slots", [
                        'store_id' => $store->id,
                        'date_from' => now()->toDateString(),
                        'date_to' => now()->addDays(7)->toDateString(),
                    ]);

                if (!$response->successful()) {
                    throw new \Exception("Failed to fetch delivery slots");
                }

                $slots = $response->json('data', []);

                Log::channel('audit')->info('Delivery slots fetched from partner', [
                    'store_id' => $store->id,
                    'slots_count' => count($slots),
                    'correlation_id' => $correlationId,
                ]);

                return [
                    'success' => true,
                    'slots' => $slots,
                ];
            } catch (Throwable $e) {
                Log::channel('audit')->error('Failed to fetch delivery slots', [
                    'store_id' => $store->id,
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);

                return [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        /**
         * Создать доставку в системе партнёра
         */
        public function createDeliveryOrder(
            GroceryStore $store,
            array $orderData,
            string $correlationId,
        ): array {
            try {
                $endpoint = self::PROVIDER_ENDPOINTS[$store->api_provider] ?? null;
                if (!$endpoint) {
                    throw new \Exception("Unsupported API provider: {$store->api_provider}");
                }

                $response = $this->http->withToken($store->api_token)
                    ->timeout(30)
                    ->post("{$endpoint}/deliveries", [
                        'order_id' => $orderData['order_id'],
                        'items' => $orderData['items'],
                        'delivery_address' => $orderData['delivery_address'],
                        'delivery_date' => $orderData['delivery_date'],
                        'customer_phone' => $orderData['customer_phone'],
                    ]);

                if (!$response->successful()) {
                    throw new \Exception("Failed to create delivery order");
                }

                $deliveryId = $response->json('delivery_id');

                Log::channel('audit')->info('Delivery order created in partner system', [
                    'store_id' => $store->id,
                    'order_id' => $orderData['order_id'],
                    'delivery_id' => $deliveryId,
                    'correlation_id' => $correlationId,
                ]);

                return [
                    'success' => true,
                    'delivery_id' => $deliveryId,
                ];
            } catch (Throwable $e) {
                Log::channel('audit')->error('Failed to create delivery order in partner system', [
                    'store_id' => $store->id,
                    'order_id' => $orderData['order_id'] ?? null,
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);

                return [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        /**
         * Получить статус доставки из партнёра
         */
        public function getDeliveryStatus(
            GroceryStore $store,
            string $deliveryId,
            string $correlationId,
        ): array {
            try {
                $endpoint = self::PROVIDER_ENDPOINTS[$store->api_provider] ?? null;
                if (!$endpoint) {
                    throw new \Exception("Unsupported API provider: {$store->api_provider}");
                }

                $response = $this->http->withToken($store->api_token)
                    ->timeout(15)
                    ->get("{$endpoint}/deliveries/{$deliveryId}");

                if (!$response->successful()) {
                    throw new \Exception("Failed to fetch delivery status");
                }

                $status = $response->json();

                Log::channel('audit')->info('Delivery status fetched', [
                    'store_id' => $store->id,
                    'delivery_id' => $deliveryId,
                    'status' => $status['status'] ?? 'unknown',
                    'correlation_id' => $correlationId,
                ]);

                return [
                    'success' => true,
                    'status' => $status['status'],
                    'location' => $status['location'] ?? null,
                    'eta' => $status['eta'] ?? null,
                ];
            } catch (Throwable $e) {
                Log::channel('audit')->error('Failed to fetch delivery status', [
                    'store_id' => $store->id,
                    'delivery_id' => $deliveryId,
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

    /**
     * Оптимизация маршрутов доставки
     * Использует OSRM (Open Source Routing Machine) или Yandex.Maps API
     */
    final readonly class RouteOptimizationService
    {
        private const OSRM_ENDPOINT = 'http://router.project-osrm.org/route/v1';

        public function __construct(
            private readonly Factory $http,
        ) {}

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
                    self::OSRM_ENDPOINT . "/trip/v1/car/{$coordinateString}",
                    ['steps' => 'true', 'geometries' => 'polyline', 'overview' => 'full']
                );

                if (!$response->successful()) {
                    throw new \Exception('OSRM route calculation failed');
                }

                $trips = $response->json('trips', []);
                if (empty($trips)) {
                    throw new \Exception('No trips found in OSRM response');
                }

                $trip = $trips[0];
                $totalDistance = $trip['distance'] ?? 0; // Метры
                $totalDuration = $trip['duration'] ?? 0; // Секунды

                // Восстанавливаем порядок доставок из маршрута
                $waypoints = $trip['waypoints'] ?? [];
                $orderedDeliveries = [];
                foreach ($waypoints as $wp) {
                    if ($wp['hint'] > 0) { // Skip store (hint=0)
                        $deliveryIndex = $wp['hint'] - 1;
                        if ($deliveryIndex < count($deliveries)) {
                            $orderedDeliveries[] = $deliveries[$deliveryIndex];
                        }
                    }
                }

                Log::channel('audit')->info('Route optimized', [
                    'deliveries_count' => count($deliveries),
                    'total_distance_km' => round($totalDistance / 1000, 2),
                    'total_duration_min' => round($totalDuration / 60, 2),
                    'correlation_id' => $correlationId,
                ]);

                return [
                    'success' => true,
                    'ordered_deliveries' => $orderedDeliveries,
                    'distance_km' => round($totalDistance / 1000, 2),
                    'duration_minutes' => round($totalDuration / 60, 2),
                ];
            } catch (Throwable $e) {
                Log::channel('audit')->error('Route optimization failed', [
                    'deliveries_count' => count($deliveries),
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);

                return [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'ordered_deliveries' => $deliveries, // Fallback: original order
                ];
            }
        }

        /**
         * Рассчитать расстояние между двумя точками (Haversine formula)
         */
        public function calculateDistance(
            float $lat1,
            float $lon1,
            float $lat2,
            float $lon2,
        ): float {
            $earthRadius = 6371000; // Metres

            $dLat = deg2rad($lat2 - $lat1);
            $dLon = deg2rad($lon2 - $lon1);

            $a = sin($dLat / 2) * sin($dLat / 2) +
                 cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
                 sin($dLon / 2) * sin($dLon / 2);

            $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

            return ($earthRadius * $c) / 1000; // Return in kilometers
        }

        /**
         * Получить время доставки с учётом расстояния
         */
        public function estimateDeliveryTime(float $distanceKm, int $deliveryOrderCount = 1): int
        {
            // Базовое время = 10 минут + 1.5 минуты на км + 3 минуты на доставку
            $baseTime = 10;
            $distanceTime = intval($distanceKm * 1.5);
            $deliveryTime = 3 * $deliveryOrderCount;

            return $baseTime + $distanceTime + $deliveryTime;
        }
}
