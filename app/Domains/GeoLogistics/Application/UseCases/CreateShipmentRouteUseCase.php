<?php

declare(strict_types=1);

namespace App\Domains\GeoLogistics\Application\UseCases;


use Psr\Log\LoggerInterface;
use App\Domains\GeoLogistics\Application\DTOs\CreateShipmentDto;
use App\Domains\GeoLogistics\Domain\Contracts\GeoRoutingServiceInterface;
use App\Domains\GeoLogistics\Domain\Contracts\ShipmentRepositoryInterface;
use App\Domains\GeoLogistics\Domain\Enums\ShipmentStatus;
use App\Domains\GeoLogistics\Domain\Models\Shipment;
use App\Domains\GeoLogistics\Domain\ValueObjects\Coordinates;
use App\Services\FraudControlService;
use Illuminate\Support\Str;

/**
 * Application Use Case: Расчёт и создание логистического маршрута доставки (ETA, Cost).
 */
final readonly class CreateShipmentRouteUseCase
{
    public function __construct(private GeoRoutingServiceInterface $geoRoutingService,
        private ShipmentRepositoryInterface $shipmentRepository,
        private FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}

    public function execute(CreateShipmentDto $dto): Shipment
    {
        $this->logger->info('Начало создания логистического маршрута', [
            'correlation_id' => $dto->correlationId,
            'delivery_order_id' => $dto->deliveryOrderId,
        ]);

        // Fraud check: аномальные дистанции или частота создания
        $this->fraud->checkGeoManipulation((string) $dto->tenantId, $dto->correlationId);

        $origin = new Coordinates($dto->pickupLat, $dto->pickupLng);
        $destination = new Coordinates($dto->dropoffLat, $dto->dropoffLng);

        // Инфраструктурный вызов расчёта (OSRM, Yandex и т.п.)
        $route = $this->geoRoutingService->calculateRouteMode($origin, $destination, 'driving');

        // Расчёт стоимости (формула: базовая цена + (расстояние / 1000) * тариф_км )
        // Здесь для каноничности: 15000 (150 руб) база + 25 руб за км. (копейки)
        $cost = 15000 + (int) (($route['distance_meters'] / 1000) * 2500);

        return $this->db->transaction(function () use ($dto, $route, $cost) {
            $shipment = new Shipment([
                'uuid' => Str::uuid()->toString(),
                'tenant_id' => $dto->tenantId,
                'delivery_order_id' => $dto->deliveryOrderId,
                'status' => ShipmentStatus::PENDING,
                'pickup_lat' => $dto->pickupLat,
                'pickup_lng' => $dto->pickupLng,
                'dropoff_lat' => $dto->dropoffLat,
                'dropoff_lng' => $dto->dropoffLng,
                'current_lat' => $dto->pickupLat, // По умолчанию курьер "у точки"
                'current_lng' => $dto->pickupLng,
                'estimated_distance_meters' => $route['distance_meters'],
                'estimated_duration_seconds' => $route['duration_seconds'],
                'calculated_cost' => $cost,
                'correlation_id' => $dto->correlationId,
            ]);

            $this->shipmentRepository->save($shipment);

            $this->logger->info('Логистический маршрут успешно материализован', [
                'correlation_id' => $dto->correlationId,
                'shipment_id' => $shipment->id,
                'cost' => $cost,
                'eta_sec' => $route['duration_seconds']
            ]);

            return $shipment;
        });
    }
}
