<?php declare(strict_types=1);

namespace App\Services\ML;


use Illuminate\Http\Request;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Cache\CacheManager;





/**
 * TrafficPredictionService — ML-предсказание времени в пути с учётом пробок.
 *
 * Правила канона:
 *  - Использует исторические данные из ClickHouse (delivery_tracks + delivery_orders)
 *  - Учитывает: час суток, день недели, район города (geo-hashed), тип ТС
 *  - Кэширование прогнозов в Redis (TTL 300 сек — пробки меняются)
 *  - Fallback: базовая скорость по типу ТС без ML
 */
final readonly class TrafficPredictionService
{
    public function __construct(
        private readonly Request $request,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
        private readonly CacheManager $cache,
    ) {}

    // Средние скорости по типам ТС в городе (км/ч) — fallback без ML
    private const BASE_SPEEDS = [
        'bike'     => 18,
        'scooter'  => 22,
        'car'      => 28,
        'van'      => 22,
        'truck'    => 18,
        'default'  => 25,
    ];

    /**
     * Предсказать время в пути (минуты) для набора маршрутов.
     *
     * @param array<int, array{from: array{lat: float, lon: float}, to: array{lat: float, lon: float}, vehicle_type?: string}> $routes
     * @return array<int, array{distance_km: float, predicted_minutes: int, confidence: float}>
     */
    public function predictTimes(array $routes): array
    {
        return array_map(fn (array $route): array => $this->predictSingleRoute($route), $routes);
    }

    /**
     * Предсказать время для одного маршрута.
     *
     * @param array{from: array{lat: float, lon: float}, to: array{lat: float, lon: float}, vehicle_type?: string} $route
     * @return array{distance_km: float, predicted_minutes: int, confidence: float}
     */
    public function predictSingleRoute(array $route): array
    {
        $vehicleType = $route['vehicle_type'] ?? 'default';
        $from        = $route['from'];
        $to          = $route['to'];

        $distanceKm = $this->haversineKm(
            (float) $from['lat'], (float) $from['lon'],
            (float) $to['lat'],   (float) $to['lon'],
        );

        $cacheKey = 'traffic:' . md5("{$from['lat']},{$from['lon']}-{$to['lat']},{$to['lon']}-{$vehicleType}-" . now()->format('YmdH'));

        return $this->cache->remember($cacheKey, 300, function () use ($distanceKm, $vehicleType, $from, $to): array {
            try {
                return $this->predictFromHistory($distanceKm, $vehicleType, $from, $to);
            } catch (\Throwable $e) {
                $this->logger->channel('audit')->warning('TrafficPredictionService: ML fallback', [
                    'error' => $e->getMessage(),
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
            ]);
                return $this->fallbackPrediction($distanceKm, $vehicleType);
            }
        });
    }

    /**
     * Предсказание на основе исторических данных из delivery_tracks.
     *
     * Алгоритм:
     * 1. Берём delivery_tracks за последние 30 дней в данном geo-квадрате
     * 2. Фильтруем по часу суток ± 1 час и дню недели
     * 3. Вычисляем медианную скорость
     * 4. Применяем к дистанции
     *
     * @param array{lat: float, lon: float} $from
     * @param array{lat: float, lon: float} $to
     * @return array{distance_km: float, predicted_minutes: int, confidence: float}
     */
    private function predictFromHistory(float $distanceKm, string $vehicleType, array $from, array $to): array
    {
        $hourNow  = (int) now()->format('H');
        $dowNow   = (int) now()->format('N'); // 1=Пн, 7=Вс

        // Geo-квадрат 0.05° ≈ 5 км
        $latMin = round((float) $from['lat'] - 0.05, 3);
        $latMax = round((float) $from['lat'] + 0.05, 3);
        $lonMin = round((float) $from['lon'] - 0.05, 3);
        $lonMax = round((float) $from['lon'] + 0.05, 3);

        $historicalSpeeds = $this->db->table('delivery_tracks')
            ->whereBetween('lat', [$latMin, $latMax])
            ->whereBetween('lon', [$lonMin, $lonMax])
            ->where('speed', '>', 0)
            ->whereRaw('EXTRACT(HOUR FROM tracked_at) BETWEEN ? AND ?', [$hourNow - 1, $hourNow + 1])
            ->whereRaw('EXTRACT(DOW FROM tracked_at) = ?', [$dowNow % 7])
            ->where('tracked_at', '>=', now()->subDays(30))
            ->pluck('speed');

        if ($historicalSpeeds->isEmpty()) {
            return $this->fallbackPrediction($distanceKm, $vehicleType);
        }

        // Медианная скорость из истории
        $sorted       = $historicalSpeeds->sort()->values();
        $medianSpeed  = (float) $sorted->median();
        $confidence   = min(1.0, $historicalSpeeds->count() / 50); // 50+ точек = 100% уверенность

        $predictedMin = $medianSpeed > 0
            ? (int) ceil($distanceKm / $medianSpeed * 60)
            : (int) ceil($distanceKm / self::BASE_SPEEDS[$vehicleType] * 60);

        return [
            'distance_km'       => $distanceKm,
            'predicted_minutes' => $predictedMin,
            'confidence'        => round($confidence, 2),
        ];
    }

    /**
     * Fallback без ML — базовая скорость по типу ТС.
     *
     * @return array{distance_km: float, predicted_minutes: int, confidence: float}
     */
    private function fallbackPrediction(float $distanceKm, string $vehicleType): array
    {
        $speed = self::BASE_SPEEDS[$vehicleType] ?? self::BASE_SPEEDS['default'];

        return [
            'distance_km'       => $distanceKm,
            'predicted_minutes' => (int) ceil($distanceKm / $speed * 60),
            'confidence'        => 0.5,
        ];
    }

    private function haversineKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $R    = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a    = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
        return round($R * 2 * atan2(sqrt($a), sqrt(1 - $a)), 3);
    }
}
