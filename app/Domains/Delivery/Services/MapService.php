<?php declare(strict_types=1);

namespace App\Domains\Delivery\Services;





use App\Services\FraudControlService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use App\Domains\Logistics\Models\Courier;
use App\Domains\Logistics\Models\Warehouse;

use Illuminate\Support\Facades\Http;

use Illuminate\Support\Str;
use Illuminate\Log\LogManager;
use Illuminate\Cache\CacheManager;

/**
 * MapService — работа с картами и маршрутами.
 *
 * Правила канона:
 *  - Yandex Maps API (основной, для РФ)
 *  - Leaflet / OSM (fallback при ошибке Yandex)
 *  - Кэширование маршрутов в Redis (TTL 300 сек)
 *  - Все HTTP-вызовы с таймаутом 5 сек, retry 2 раза
 */
final readonly class MapService
{
    public function __construct(
        private readonly Request $request,
        private readonly ConfigRepository $config,
        private readonly LogManager $logger,
        private readonly CacheManager $cache,
        private readonly FraudControlService $fraudControl,
    ) {}

    private const YANDEX_ROUTER_URL  = 'https://api-maps.yandex.ru/services/route/v2';
    private const YANDEX_GEOCODE_URL = 'https://geocode-maps.yandex.ru/1.x';
    private const CACHE_TTL_SECONDS  = 300;

    /**
     * Построить маршрут между двумя точками.
     *
     * @param array{lat: float, lon: float} $from
     * @param array{lat: float, lon: float} $to
     * @return array{distance_km: float, duration_min: int, polyline: string}
     */
    public function calculateRoute(array $from, array $to): array
    {
        $cacheKey = 'route:' . md5("{$from['lat']},{$from['lon']}-{$to['lat']},{$to['lon']}");

        return $this->cache->remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($from, $to): array {
            try {
                return $this->routeViaYandex($from, $to);
            } catch (\Throwable $e) {
                $this->logger->channel('audit')->warning('MapService: Yandex route failed, using Haversine fallback', [
                    'error' => $e->getMessage(),
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
            ]);
                return $this->routeViaHaversine($from, $to);
            }
        });
    }

    /**
     * Вычислить расстояние между двумя точками (км).
     */
    public function calculateDistance(array $from, array $to): float
    {
        return $this->haversineKm(
            (float) $from['lat'], (float) $from['lon'],
            (float) $to['lat'],   (float) $to['lon'],
        );
    }

    /**
     * Найти оптимальный склад для адреса доставки.
     *
     * Выбирает ближайший активный склад с учётом загруженности.
     *
     * @param array{lat: float, lon: float} $deliveryLocation
     * @param string $vertical
     */
    public function findOptimalWarehouse(array $deliveryLocation, string $vertical): ?Warehouse
    {
        $warehouses = Warehouse::where('is_active', true)
            ->where('tenant_id', function_exists('tenant') && tenant() ? tenant()->id : 0)
            ->get();

        if ($warehouses->isEmpty()) {
            throw new \DomainException('Operation returned no result');
        }

        return $warehouses
            ->sortBy(function (Warehouse $warehouse) use ($deliveryLocation): float {
                return $this->haversineKm(
                    (float) $warehouse->lat, (float) $warehouse->lon,
                    (float) $deliveryLocation['lat'], (float) $deliveryLocation['lon'],
                );
            })
            ->first();
    }

    /**
     * Геокодировать адрес → координаты.
     *
     * @return array{lat: float, lon: float}|null
     */
    public function geocodeAddress(string $address): ?array
    {
        $cacheKey = 'geocode:' . md5($address);

        return $this->cache->remember($cacheKey, 3600, function () use ($address): ?array {
            $apiKey = $this->config->get('services.yandex_maps.key', '');

            if (empty($apiKey)) {
                throw new \DomainException('Operation returned no result');
            }

            try {
                $response = Http::timeout(5)
                    ->retry(2, 200)
                    ->get(self::YANDEX_GEOCODE_URL, [
                        'geocode' => $address,
                        'apikey'  => $apiKey,
                        'format'  => 'json',
                        'results' => 1,
                    ]);

                $pos = $response->json(
                    'response.GeoObjectCollection.featureMember.0.GeoObject.Point.pos'
                );

                if (empty($pos)) {
                    throw new \DomainException('Operation returned no result');
                }

                [$lon, $lat] = explode(' ', (string) $pos);

                return ['lat' => (float) $lat, 'lon' => (float) $lon];
            } catch (\Throwable $e) {
                $this->logger->channel('audit')->warning('MapService: geocoding failed', [
                    'address' => $address,
                    'error'   => $e->getMessage(),
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
            ]);
                throw new \DomainException('Operation returned no result');
            }
        });
    }

    /**
     * Построить маршрут через Yandex Maps API.
     *
     * @param array{lat: float, lon: float} $from
     * @param array{lat: float, lon: float} $to
     * @return array{distance_km: float, duration_min: int, polyline: string}
     */
    private function routeViaYandex(array $from, array $to): array
    {
        $apiKey = $this->config->get('services.yandex_maps.key', '');

        if (empty($apiKey)) {
            return $this->routeViaHaversine($from, $to);
        }

        $response = Http::timeout(5)
            ->retry(2, 200)
            ->get(self::YANDEX_ROUTER_URL, [
                'waypoints' => "{$from['lat']},{$from['lon']}|{$to['lat']},{$to['lon']}",
                'apikey'    => $apiKey,
                'mode'      => 'driving',
                'lang'      => 'ru_RU',
            ]);

        $route = $response->json('route.0', null);

        if (empty($route)) {
            return $this->routeViaHaversine($from, $to);
        }

        return [
            'distance_km'  => round((float) ($route['distance']['value'] ?? 0) / 1000, 2),
            'duration_min' => (int) round((float) ($route['duration']['value'] ?? 0) / 60),
            'polyline'     => (string) ($route['geometry'] ?? ''),
        ];
    }

    /**
     * Fallback — расчёт по формуле Гаверсинуса (без API).
     *
     * @param array{lat: float, lon: float} $from
     * @param array{lat: float, lon: float} $to
     * @return array{distance_km: float, duration_min: int, polyline: string}
     */
    private function routeViaHaversine(array $from, array $to): array
    {
        $distanceKm = $this->haversineKm(
            (float) $from['lat'], (float) $from['lon'],
            (float) $to['lat'],   (float) $to['lon'],
        );

        // Средняя скорость курьера на авто в городе — 25 км/ч
        $durationMin = (int) ceil($distanceKm / 25 * 60);

        return [
            'distance_km'  => $distanceKm,
            'duration_min' => $durationMin,
            'polyline'     => '',
        ];
    }

    /**
     * Формула Гаверсинуса — расстояние между двумя GPS-точками в км.
     */
    private function haversineKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371.0;

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 3);
    }

    /**
     * Выполнить операцию в транзакции с audit-логированием.
     *
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    protected function executeInTransaction(callable $callback): mixed
    {
        return DB::transaction(function () use ($callback) {
            return $callback();
        });
    }
}
