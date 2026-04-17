<?php declare(strict_types=1);

namespace App\Services\Delivery;


use Illuminate\Http\Request;
use App\Domains\Logistics\Models\Courier;
use App\Domains\Logistics\Models\DeliveryOrder;
use App\Services\FraudControlService;
use App\Services\ML\TrafficPredictionService;
use App\Services\WalletService;
use App\Services\Geo\GeoService;
use App\Services\Geo\GeoTelemetryService;
use Illuminate\Support\Collection;


use Illuminate\Support\Str;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Auth\Guard;

/**
 * RouteOptimizationService — оптимизация маршрутов доставки.
 *
 * Правила канона:
 *  - Алгоритм: Greedy nearest-neighbour + 2-opt улучшение (VRP без внешних зависимостей)
 *  - ML: TrafficPredictionService предсказывает реальное время с учётом пробок
 *  - Маршрут сохраняется в logistics_delivery_orders.route_json
 *  - Бонус курьеру за экономию (если новый маршрут быстрее предыдущего)
 *  - Перерасчёт каждые 3 минуты для активных курьеров (RouteOptimizationJob)
 *  - B2C и B2B имеют разные приоритеты и окна доставки
 *  - Fraud-check обязателен перед перерасчётом
 */
final readonly class RouteOptimizationService
{
    public function __construct(
        private readonly Request $request,
        private GeoService $geo,
        private TrafficPredictionService $trafficML,
        private GeotrackingService $geoTracking,
        private WalletService $wallet,
        private FraudControlService $fraud,
        private GeoTelemetryService $geoTelemetry,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
        private readonly Guard $guard,
    ) {}

    /**
     * Оптимизировать маршрут для курьера по его активным заказам.
     *
     * @param int   $courierId
     * @param int[] $orderIds
     * @return array{
     *   route: array<int, array{order_id: int, lat: float, lon: float, estimated_minutes: int}>,
     *   total_distance_km: float,
     *   total_minutes: int,
     *   algorithm: string,
     * }
     */
    public function optimizeForCourier(int $courierId, array $orderIds): array
    {
        $correlationId = Str::uuid()->toString();

        $this->fraud->check((int) $this->guard->id(), 'route_optimization', 0, $this->request->ip(), null, $correlationId);

        $currentLocation = $this->geo->getCurrentLocation($courierId);
        $orders          = DeliveryOrder::whereIn('id', $orderIds)
            ->where('courier_id', $courierId)
            ->whereIn('status', ['assigned', 'picked_up', 'in_transit'])
            ->get();

        if ($orders->isEmpty()) {
            return $this->emptyRoute();
        }

        // Строим матрицу точек доставки
        $points = $this->buildPoints($currentLocation, $orders);

        // Greedy + 2-opt
        $optimizedPoints = $this->greedyNearest($points);
        $optimizedPoints = $this->twoOptImprove($optimizedPoints);

        // ML-предсказание времени с пробками
        $routes = $this->buildRoutesForML($optimizedPoints);
        $times  = $this->trafficML->predictTimes($routes);

        $finalRoute = $this->assembleFinalRoute($optimizedPoints, $times);

        // Record telemetry
        $this->geoTelemetry->recordRoute('osm', true, 0, $finalRoute['total_distance_km']);

        // Сохраняем маршрут в каждый заказ
        $previousTotalMin = $this->getPreviousTotalMinutes($courierId);
        $this->saveRoutes($finalRoute['route'], $correlationId);

        // Бонус за экономию времени
        $this->maybeAwardEfficiencyBonus($courierId, $previousTotalMin, $finalRoute['total_minutes'], $correlationId);

        $this->logger->channel('audit')->info('Route optimized', [
            'courier_id'         => $courierId,
            'orders_count'       => $orders->count(),
            'total_distance_km'  => $finalRoute['total_distance_km'],
            'total_minutes'      => $finalRoute['total_minutes'],
            'algorithm'          => $finalRoute['algorithm'],
            'correlation_id'     => $correlationId,
        ]);

        return $finalRoute;
    }

    // ─────────────────────────────────────────────────────────────
    // PRIVATE: построение точек
    // ─────────────────────────────────────────────────────────────

    /**
     * @param array{lat: float, lon: float}|null $currentLocation
     * @return array<int, array{order_id: int|null, lat: float, lon: float, is_depot: bool, priority: int}>
     */
    private function buildPoints(?array $currentLocation, Collection $orders): array
    {
        $points = [];

        // Стартовая точка (текущее местоположение курьера)
        $points[] = [
            'order_id' => null,
            'lat'      => $currentLocation['lat'] ?? 55.751244,
            'lon'      => $currentLocation['lon'] ?? 37.618423,
            'is_depot' => true,
            'priority' => 0,
        ];

        foreach ($orders as $order) {
            $dropoff = is_array($order->dropoff_point)
                ? $order->dropoff_point
                : json_decode((string) $order->dropoff_point, true);

            $isBusiness = !empty($order->business_group_id);

            $points[] = [
                'order_id' => $order->id,
                'lat'      => (float) ($dropoff['lat'] ?? 0),
                'lon'      => (float) ($dropoff['lon'] ?? 0),
                'is_depot' => false,
                'priority' => $isBusiness ? 2 : 1, // B2B — выше приоритет
            ];
        }

        return $points;
    }

    // ─────────────────────────────────────────────────────────────
    // PRIVATE: Greedy nearest-neighbour
    // ─────────────────────────────────────────────────────────────

    /**
     * @param array<int, array{order_id: int|null, lat: float, lon: float, is_depot: bool, priority: int}> $points
     * @return array<int, array{order_id: int|null, lat: float, lon: float, is_depot: bool, priority: int}>
     */
    private function greedyNearest(array $points): array
    {
        if (count($points) <= 2) {
            return $points;
        }

        $depot     = array_shift($points);
        $optimized = [$depot];
        $remaining = $points;

        $currentLat = $depot['lat'];
        $currentLon = $depot['lon'];

        while (!empty($remaining)) {
            // Сортируем: сначала по приоритету (B2B=2 > B2C=1), затем по дистанции
            usort($remaining, function (array $a, array $b) use ($currentLat, $currentLon): int {
                if ($a['priority'] !== $b['priority']) {
                    return $b['priority'] <=> $a['priority'];
                }
                $distA = $this->mapService->calculateDistance(
                    ['lat' => $currentLat, 'lon' => $currentLon],
                    ['lat' => $a['lat'], 'lon' => $a['lon']],
                );
                $distB = $this->mapService->calculateDistance(
                    ['lat' => $currentLat, 'lon' => $currentLon],
                    ['lat' => $b['lat'], 'lon' => $b['lon']],
                );
                return $distA <=> $distB;
            });

            $next       = array_shift($remaining);
            $optimized[] = $next;
            $currentLat = $next['lat'];
            $currentLon = $next['lon'];
        }

        return $optimized;
    }

    // ─────────────────────────────────────────────────────────────
    // PRIVATE: 2-opt улучшение
    // ─────────────────────────────────────────────────────────────

    /**
     * @param array<int, array{order_id: int|null, lat: float, lon: float, is_depot: bool, priority: int}> $route
     * @return array<int, array{order_id: int|null, lat: float, lon: float, is_depot: bool, priority: int}>
     */
    private function twoOptImprove(array $route): array
    {
        $n       = count($route);
        $improved = true;

        while ($improved) {
            $improved = false;
            for ($i = 1; $i < $n - 1; $i++) {
                for ($j = $i + 1; $j < $n; $j++) {
                    $currentDist = $this->mapService->calculateDistance(
                            ['lat' => $route[$i - 1]['lat'], 'lon' => $route[$i - 1]['lon']],
                            ['lat' => $route[$i]['lat'],     'lon' => $route[$i]['lon']],
                        )
                        + $this->mapService->calculateDistance(
                            ['lat' => $route[$j]['lat'],     'lon' => $route[$j]['lon']],
                            ['lat' => $route[($j + 1) % $n]['lat'], 'lon' => $route[($j + 1) % $n]['lon']],
                        );

                    $newDist = $this->mapService->calculateDistance(
                            ['lat' => $route[$i - 1]['lat'], 'lon' => $route[$i - 1]['lon']],
                            ['lat' => $route[$j]['lat'],     'lon' => $route[$j]['lon']],
                        )
                        + $this->mapService->calculateDistance(
                            ['lat' => $route[$i]['lat'],     'lon' => $route[$i]['lon']],
                            ['lat' => $route[($j + 1) % $n]['lat'], 'lon' => $route[($j + 1) % $n]['lon']],
                        );

                    if ($newDist < $currentDist - 0.001) {
                        $route    = array_merge(
                            array_slice($route, 0, $i),
                            array_reverse(array_slice($route, $i, $j - $i + 1)),
                            array_slice($route, $j + 1),
                        );
                        $improved = true;
                    }
                }
            }
        }

        return $route;
    }

    // ─────────────────────────────────────────────────────────────
    // PRIVATE: ML-маршруты
    // ─────────────────────────────────────────────────────────────

    /**
     * @param array<int, array{lat: float, lon: float}> $points
     * @return array<int, array{from: array{lat: float, lon: float}, to: array{lat: float, lon: float}}>
     */
    private function buildRoutesForML(array $points): array
    {
        $routes = [];
        for ($i = 0; $i < count($points) - 1; $i++) {
            $routes[] = [
                'from' => ['lat' => $points[$i]['lat'], 'lon' => $points[$i]['lon']],
                'to'   => ['lat' => $points[$i + 1]['lat'], 'lon' => $points[$i + 1]['lon']],
            ];
        }
        return $routes;
    }

    /**
     * @param array<int, array{order_id: int|null, lat: float, lon: float}> $points
     * @param array<int, array{distance_km: float, predicted_minutes: int}> $times
     * @return array{route: array, total_distance_km: float, total_minutes: int, algorithm: string}
     */
    private function assembleFinalRoute(array $points, array $times): array
    {
        $route        = [];
        $totalDist    = 0.0;
        $totalMinutes = 0;

        foreach ($points as $i => $point) {
            if ($point['is_depot'] ?? false) {
                continue;
            }

            $leg = $times[$i - 1] ?? ['distance_km' => 0, 'predicted_minutes' => 0];

            $totalDist    += $leg['distance_km'];
            $totalMinutes += $leg['predicted_minutes'];

            $route[] = [
                'order_id'          => $point['order_id'],
                'lat'               => $point['lat'],
                'lon'               => $point['lon'],
                'leg_distance_km'   => $leg['distance_km'],
                'estimated_minutes' => $leg['predicted_minutes'],
            ];
        }

        return [
            'route'             => $route,
            'total_distance_km' => round($totalDist, 2),
            'total_minutes'     => $totalMinutes,
            'algorithm'         => 'greedy_2opt_mltraffic',
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // PRIVATE: Сохранение и бонусы
    // ─────────────────────────────────────────────────────────────

    private function saveRoutes(array $route, string $correlationId): void
    {
        foreach ($route as $stop) {
            if (empty($stop['order_id'])) {
                continue;
            }

            DeliveryOrder::where('id', $stop['order_id'])->update([
                'route_json'              => json_encode($stop),
                'estimated_delivery_at'   => now()->addMinutes((int) $stop['estimated_minutes']),
                'correlation_id'          => $correlationId,
            ]);
        }
    }

    private function getPreviousTotalMinutes(int $courierId): int
    {
        // Берём сумму estimated_minutes из активных заказов до перерасчёта
        $orders = DeliveryOrder::where('courier_id', $courierId)
            ->whereIn('status', ['assigned', 'picked_up', 'in_transit'])
            ->whereNotNull('estimated_delivery_at')
            ->get();

        return $orders->sum(
            fn (DeliveryOrder $o): int => max(0, (int) now()->diffInMinutes($o->estimated_delivery_at))
        );
    }

    private function maybeAwardEfficiencyBonus(
        int    $courierId,
        int    $previousMinutes,
        int    $newMinutes,
        string $correlationId,
    ): void {
        if ($previousMinutes <= 0 || $newMinutes >= $previousMinutes) {
            return;
        }

        $savedMinutes = $previousMinutes - $newMinutes;

        // 5 ₽ за каждую сэкономленную минуту
        $bonusKopecks = $savedMinutes * 500;

        $courier = Courier::find($courierId);
        if ($courier === null) {
            return;
        }

        $walletId = $this->db->table('wallets')
            ->where('user_id', $courier->user_id)
            ->value('id');

        if ($walletId === null) {
            return;
        }

        $this->wallet->credit((int) $walletId, $bonusKopecks, \App\Domains\Wallet\Enums\BalanceTransactionType::BONUS, $correlationId, null, null, [
            'courier_id'     => $courierId,
            'saved_minutes'  => $savedMinutes,
            'bonus_kopecks'  => $bonusKopecks,
            'correlation_id' => $correlationId,
        ]);
    }

    private function emptyRoute(): array
    {
        return [
            'route'             => [],
            'total_distance_km' => 0.0,
            'total_minutes'     => 0,
            'algorithm'         => 'empty',
        ];
    }
}
