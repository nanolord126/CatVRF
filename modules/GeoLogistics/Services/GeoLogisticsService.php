<?php

declare(strict_types=1);

namespace Modules\GeoLogistics\Services;

use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Log\LogManager;
use Modules\Common\Services\AbstractTechnicalVerticalService;
use Modules\GeoLogistics\Models\DeliveryRoute;

/**
 * Сервис геологистики и маршрутизации.
 *
 * КАНОН 2026:
 * - Расчёт маршрутов через OSRM / Yandex Maps API
 * - Surge-коэффициент на основе плотности заказов в зоне
 * - Все запросы логируются с correlation_id
 * - Статические фасады запрещены — только DI
 */
final class GeoLogisticsService extends AbstractTechnicalVerticalService
{
    public function __construct(
        private readonly LogManager $log,
        private readonly HttpClient $http,
    ) {}

    public function isEnabled(): bool
    {
        return (bool) config('geologistics.enabled', true);
    }

    // ──────────────────────────────────────────────────────────────────
    //  Routing
    // ──────────────────────────────────────────────────────────────────

    /**
     * Рассчитать маршрут между двумя точками (OSRM / Yandex).
     *
     * @param array{lat: float, lon: float} $from
     * @param array{lat: float, lon: float} $to
     *
     * @return array{distance_km: float, duration_min: int, surge_multiplier: float}
     *
     * @throws \RuntimeException При ошибке внешнего API
     */
    public function calculateRoute(array $from, array $to): array
    {
        $correlationId = $this->getCorrelationId();

        $this->log->channel('audit')->info('geologistics.route.start', [
            'correlation_id' => $correlationId,
            'from'           => $from,
            'to'             => $to,
        ]);

        try {
            $apiKey = config('geologistics.api_key', '');
            $driver = config('geologistics.driver', 'osrm');

            $result = match ($driver) {
                'yandex' => $this->routeViaYandex($from, $to, $apiKey),
                default  => $this->routeViaOsrm($from, $to),
            };

            $result['surge_multiplier'] = $this->calculateSurge($from);

            $this->log->channel('audit')->info('geologistics.route.success', [
                'correlation_id'   => $correlationId,
                'distance_km'      => $result['distance_km'],
                'duration_min'     => $result['duration_min'],
                'surge_multiplier' => $result['surge_multiplier'],
            ]);

            return $result;
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('geologistics.route.error', [
                'correlation_id' => $correlationId,
                'error'          => $e->getMessage(),
                'trace'          => $e->getTraceAsString(),
            ]);

            throw new \RuntimeException('Ошибка расчёта маршрута: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Рассчитать стоимость доставки в копейках.
     *
     * @param array $from  Координаты отправки
     * @param array $to    Координаты получения
     * @param float $weightKg Вес посылки в кг
     *
     * @return int Стоимость в копейках
     */
    public function calculateDeliveryCost(array $from, array $to, float $weightKg = 1.0): int
    {
        $route      = $this->calculateRoute($from, $to);
        $baseCost   = (int) config('geologistics.base_cost_kopek', 15000); // 150 ₽
        $perKm      = (int) config('geologistics.per_km_kopek', 500);      // 5 ₽/км
        $perKg      = (int) config('geologistics.per_kg_kopek', 200);      // 2 ₽/кг

        $cost = (int) (
            $baseCost
            + ($route['distance_km'] * $perKm)
            + ($weightKg * $perKg)
        );

        return (int) ($cost * $route['surge_multiplier']);
    }

    /**
     * Получить surge-коэффициент для зоны по координатам.
     * Если в радиусе 2 км более 50 заказов/час — surge 1.2+.
     *
     * @param array{lat: float, lon: float} $location
     */
    public function calculateSurge(array $location): float
    {
        $tenantId = isset($this->tenant) ? $this->resolveTenantId() : null;

        $hotOrdersCount = DeliveryRoute::when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->where('created_at', '>=', now()->subHour())
            ->whereRaw(
                '(6371 * acos(cos(radians(?)) * cos(radians(start_latitude)) * cos(radians(start_longitude) - radians(?)) + sin(radians(?)) * sin(radians(start_latitude)))) < 2',
                [$location['lat'] ?? 0, $location['lon'] ?? 0, $location['lat'] ?? 0]
            )
            ->count();

        return match (true) {
            $hotOrdersCount > 100 => 1.5,
            $hotOrdersCount > 50  => 1.2,
            $hotOrdersCount > 20  => 1.1,
            default               => 1.0,
        };
    }

    // ──────────────────────────────────────────────────────────────────
    //  Private routing drivers
    // ──────────────────────────────────────────────────────────────────

    private function routeViaOsrm(array $from, array $to): array
    {
        $url = sprintf(
            'http://router.project-osrm.org/route/v1/driving/%s,%s;%s,%s?overview=false',
            $from['lon'] ?? 0, $from['lat'] ?? 0,
            $to['lon'] ?? 0,   $to['lat'] ?? 0,
        );

        $response = $this->http->timeout(5)->get($url);

        if (!$response->successful()) {
            throw new \RuntimeException('OSRM API error: ' . $response->status());
        }

        $data     = $response->json();
        $route    = $data['routes'][0] ?? null;

        if ($route === null) {
            throw new \RuntimeException('OSRM: no route found');
        }

        return [
            'distance_km' => round($route['distance'] / 1000, 2),
            'duration_min' => (int) ceil($route['duration'] / 60),
        ];
    }

    private function routeViaYandex(array $from, array $to, string $apiKey): array
    {
        $response = $this->http->timeout(5)->get('https://maps-api.yandex.ru/v1/route', [
            'apikey'       => $apiKey,
            'waypoints'    => "{$from['lat']},{$from['lon']}|{$to['lat']},{$to['lon']}",
            'type'         => 'driving',
            'lang'         => 'ru_RU',
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Yandex Maps API error: ' . $response->status());
        }

        $data = $response->json();
        $leg  = $data['route']['legs'][0] ?? null;

        if ($leg === null) {
            throw new \RuntimeException('Yandex Maps: no route found');
        }

        return [
            'distance_km'  => round(($leg['distanceMeters'] ?? 0) / 1000, 2),
            'duration_min' => (int) ceil(($leg['durationSeconds'] ?? 0) / 60),
        ];
    }
}

