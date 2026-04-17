<?php declare(strict_types=1);

namespace App\Services\Delivery;


use Illuminate\Http\Request;
use App\Domains\Logistics\Models\Courier;
use App\Domains\Logistics\Models\DeliveryOrder;
use App\Domains\Logistics\Models\DeliveryTrack;
use App\Events\CourierLocationUpdated;
use App\Services\FraudControlService;
use App\Services\ML\BigDataAggregatorService;
use App\Services\Geo\GeoPrivacyService;
use App\Services\Geo\GeoTrackingStreamService;
use App\Services\Geo\GeoTelemetryService;
use Illuminate\Support\Collection;


use Illuminate\Support\Str;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Auth\Guard;

/**
 * GeotrackingService — реал-тайм геотрекинг курьеров.
 *
 * Правила канона:
 *  - Обновление позиции каждые 3 секунды (запускается из мобильного приложения)
 *  - Broadcast через Laravel Echo (Channel: delivery.{orderId}, courier.{courierId}.location)
 *  - Все координаты логируются в delivery_tracks с correlation_id
 *  - Fraud-check + rate-limit на все обновления позиции
 *  - Tenant-scoping: курьер видит только свои заказы
 *  - Redis Streams для гарантированной доставки событий
 *  - Privacy control для 152-ФЗ compliance
 */
final readonly class GeotrackingService
{
    public function __construct(
        private readonly Request $request,
        private FraudControlService    $fraud,
        private BigDataAggregatorService $bigData,
        private GeoPrivacyService $geoPrivacy,
        private GeoTrackingStreamService $trackingStream,
        private GeoTelemetryService $geoTelemetry,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
        private readonly Guard $guard,
    ) {}

    /**
     * Обновить текущую позицию курьера и разослать всем подписчикам.
     */
    public function updateCourierLocation(
        int    $courierId,
        float  $lat,
        float  $lon,
        float  $speed   = 0.0,
        float  $bearing = 0.0,
    ): DeliveryTrack {
        $correlationId = $this->request->header('X-Correlation-ID') ?? Str::uuid()->toString();
        $startTime = microtime(true);

        $this->fraud->check(
            userId: (int) $this->guard->id(),
            operationType: 'courier_location_update',
            amount: 0,
            ipAddress: $this->request->ip(),
            deviceFingerprint: null,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($courierId, $lat, $lon, $speed, $bearing, $correlationId, $startTime): DeliveryTrack {
            // 1. Находим активный заказ курьера
            $activeOrder = DeliveryOrder::where('courier_id', $courierId)
                ->whereIn('status', ['assigned', 'picked_up', 'in_transit'])
                ->first();

            // 2. Пишем трек в PostgreSQL с анонимизацией
            $anonymizedCoords = $this->geoPrivacy->anonymizeCoordinates($lat, $lon, 'courier_tracking');
            
            $track = DeliveryTrack::create([
                'delivery_order_id' => $activeOrder?->id,
                'courier_id'        => $courierId,
                'lat'               => $anonymizedCoords['lat'],
                'lon'               => $anonymizedCoords['lon'],
                'speed'             => $speed,
                'bearing'           => $bearing,
                'correlation_id'    => $correlationId,
            ]);

            // 3. Обновляем текущую позицию курьера
            Courier::where('id', $courierId)->update([
                'current_location'      => json_encode($anonymizedCoords),
                'last_location_update'  => now(),
            ]);

            // 4. Добавляем в Redis Stream для гарантированной доставки
            $this->trackingStream->addLocationUpdate(
                entityId: $courierId,
                entityType: 'courier',
                lat: $anonymizedCoords['lat'],
                lon: $anonymizedCoords['lon'],
                speed: $speed,
                bearing: $bearing,
                correlationId: $correlationId,
            );

            // 5. Пишем в ClickHouse для аналитики (анонимизированно)
            $this->bigData->insertGeoEvent([
                'courier_id'        => $courierId,
                'lat'               => round($anonymizedCoords['lat'], 3),
                'lon'               => round($anonymizedCoords['lon'], 3),
                'speed'             => $speed,
                'delivery_order_id' => $activeOrder?->id,
                'correlation_id'    => $correlationId,
                'tracked_at'        => now()->toDateTimeString(),
            ]);

            // 6. Broadcast реал-тайм всем подписчикам
            broadcast(new CourierLocationUpdated(
                courierId:       $courierId,
                lat:             $anonymizedCoords['lat'],
                lon:             $anonymizedCoords['lon'],
                speed:           $speed,
                bearing:         $bearing,
                deliveryOrderId: $activeOrder?->id,
                correlationId:   $correlationId,
            ))->toOthers();

            // 7. Record telemetry
            $latencyMs = (microtime(true) - $startTime) * 1000;
            $this->geoTelemetry->recordTrackingUpdate('courier');

            $this->logger->channel('audit')->info('Courier location updated', [
                'courier_id'        => $courierId,
                'delivery_order_id' => $activeOrder?->id,
                'correlation_id'    => $correlationId,
                'privacy_precision' => $anonymizedCoords['precision'],
                'latency_ms'        => round($latencyMs, 2),
            ]);

            return $track;
        });
    }

    /**
     * Получить последние N точек трека для заказа (для отрисовки маршрута).
     */
    public function getLiveTrack(int $deliveryOrderId, int $limit = 50): Collection
    {
        return DeliveryTrack::where('delivery_order_id', $deliveryOrderId)
            ->orderBy('tracked_at', 'desc')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();
    }

    /**
     * Запустить трекинг при назначении заказа.
     * Вызывается из CourierService::assignCourier().
     */
    public function startTracking(DeliveryOrder $order): void
    {
        $this->logger->channel('audit')->info('GeoTracking started for delivery order', [
            'delivery_order_id' => $order->id,
            'courier_id'        => $order->courier_id,
            'correlation_id'    => $order->correlation_id ?? Str::uuid()->toString(),
        ]);

        // Создаём consumer group для Redis Streams
        $this->trackingStream->createConsumerGroup('delivery_worker');

        // GeotrackingJob запускается с мобильного приложения курьера каждые 3 сек.
        // Серверная часть только хранит и ретранслирует.
        // Принудительный запрос первой позиции через push-уведомление:
        \App\Jobs\GeotrackingJob::dispatch($order->id)
            ->onQueue('geo');
    }

    /**
     * Получить текущую позицию курьера из БД (кэш recent track).
     *
     * @return array{lat: float, lon: float}|null
     */
    public function getCurrentLocation(int $courierId): ?array
    {
        $courier = Courier::find($courierId);

        if ($courier === null || empty($courier->current_location)) {
            throw new \DomainException('Operation returned no result');
        }

        $loc = is_string($courier->current_location)
            ? json_decode($courier->current_location, true)
            : (array) $courier->current_location;

        return [
            'lat' => (float) ($loc['lat'] ?? 0),
            'lon' => (float) ($loc['lon'] ?? 0),
        ];
    }

    /**
     * Пометить курьера онлайн/оффлайн.
     */
    public function setOnlineStatus(int $courierId, bool $isOnline): void
    {
        Courier::where('id', $courierId)->update(['is_online' => $isOnline]);

        $this->logger->channel('audit')->info('Courier online status changed', [
            'courier_id' => $courierId,
            'is_online'  => $isOnline,
            'correlation_id' => Str::uuid()->toString(),
        ]);
    }
}
