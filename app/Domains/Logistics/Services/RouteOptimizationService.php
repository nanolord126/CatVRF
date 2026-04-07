<?php declare(strict_types=1);

namespace App\Domains\Logistics\Services;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class RouteOptimizationService
{

    private readonly string $correlationId;


    public function __construct(private FraudControlService $fraud,
            string $correlationId = '',
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {
            $this->correlationId = $this->correlationId ?: (string) Str::uuid();
        }

        /**
         * Оптимизировать маршрут для курьера с несколькими точками (TSP Algorithm)
         * Использует эвристику "Ближайшего соседа" + запрос к внешнему роутинг-сервису
         */
        public function optimizeCourierRoute(Courier $courier, array $orderIds): Route
        {
            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');

            return $this->db->transaction(function () use ($courier, $orderIds) {
                $orders = DeliveryOrder::whereIn('id', $orderIds)
                    ->where('tenant_id', tenant()?->id)
                    ->get();

                if ($orders->isEmpty()) {
                    throw new \InvalidArgumentException("No valid orders found for route optimization");
                }

                // 1. Сортировка по удаленности от курьера (Simple Greedy TSP)
                $startPoint = [
                    'lat' => $courier->last_lat,
                    'lon' => $courier->last_lon
                ];

                $optimizedPoints = $this->calculateOptimalSequence($startPoint, $orders);

                // 2. Генерация полилинии (эмуляция вызова OSRM API)
                // В реальности здесь: Http::get("http://router.project-osrm.org/route/v1/driving/...")
                $polyline = $this->generatePolylineForPoints($optimizedPoints);

                // 3. Создание или обновление маршрута
                $route = Route::create([
                    'uuid' => (string) Str::uuid(),
                    'tenant_id' => tenant()?->id,
                    'courier_id' => $courier->id,
                    'status' => 'active',
                    'polyline' => json_encode($polyline),
                    'estimated_distance_m' => $this->sumDistance($optimizedPoints),
                    'estimated_duration_min' => $this->sumDuration($optimizedPoints),
                    'correlation_id' => $this->correlationId,
                    'tags' => ['mode' => 'ai_optimized', 'orders_count' => count($orderIds)]
                ]);

                $this->logger->info('Route optimized by AI', [
                    'courier_id' => $courier->id,
                    'route_uuid' => $route->uuid,
                    'orders' => $orderIds,
                    'correlation_id' => $this->correlationId
                ]);

                return $route;
            });
        }

        /**
         * Группировка заказов (Batching)
         * Находит заказы, которые "по пути" текущему курьеру
         */
        public function findNearbyBatchOrders(DeliveryOrder $mainOrder, float $radiusKm = 2.0): Collection
        {
            // Гео-запрос для поиска заказов в радиусе финиша основного заказа
            return DeliveryOrder::where('status', 'pending')
                ->where('tenant_id', tenant()?->id)
                ->where('id', '!=', $mainOrder->id)
                ->get()
                ->filter(function ($other) use ($mainOrder, $radiusKm) {
                    return $this->distance($mainOrder->dropoff_lat, $mainOrder->dropoff_lon, $other->pickup_lat, $other->pickup_lon) <= $radiusKm;
                });
        }

        private function calculateOptimalSequence(array $start, Collection $orders): array
        {
            $points = [];
            $current = $start;
            $remaining = $orders->all();

            while (!empty($remaining)) {
                $nearestKey = null;
                $minDist = PHP_FLOAT_MAX;

                foreach ($remaining as $key => $order) {
                    $d = $this->distance($current['lat'], $current['lon'], $order->pickup_lat, $order->pickup_lon);
                    if ($d < $minDist) {
                        $minDist = $d;
                        $nearestKey = $key;
                    }
                }

                $points[] = $remaining[$nearestKey];
                $current = ['lat' => $remaining[$nearestKey]->pickup_lat, 'lon' => $remaining[$nearestKey]->pickup_lon];
                unset($remaining[$nearestKey]);
            }

            return $points;
        }

        private function distance(float $lat1, float $lon1, float $lat2, float $lon2): float
        {
            $earthRadius = 6371;
            $dLat = deg2rad($lat2 - $lat1);
            $dLon = deg2rad($lon2 - $lon1);
            $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
            return $earthRadius * 2 * atan2(sqrt($a), sqrt(1-$a));
        }

        private function generatePolylineForPoints(array $points): array
        {
            // Mock-генерация точек между основными узлами
            return array_map(fn($p) => ['lat' => $p->pickup_lat, 'lng' => $p->pickup_lon], $points);
        }

        private function sumDistance(array $points): int
        {
            return count($points) * 1500; // Примерная заглушка 1.5км на точку
        }

        private function sumDuration(array $points): int
        {
            return count($points) * 10; // 10 минут на точку
        }
}
