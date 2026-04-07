<?php

declare(strict_types=1);

namespace App\Domains\Delivery\Application\UseCases;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use App\Domains\Delivery\Domain\DTOs\DeliveryData;
use App\Domains\Delivery\Domain\Entities\Delivery;
use App\Domains\Delivery\Domain\Enums\DeliveryStatus;
use App\Domains\Delivery\Domain\Events\DeliveryStatusChanged;
use App\Domains\Delivery\Domain\Repositories\DeliveryRepositoryInterface;
use App\Domains\GeoLogistics\Services\GeoLogisticsServiceInterface;
use App\Services\FraudControlService;
use Illuminate\Support\Str;

final class CreateDeliveryUseCase
{
    public function __construct(private readonly DeliveryRepositoryInterface $deliveryRepository,
        private readonly GeoLogisticsServiceInterface $geoLogisticsService,
        private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {

    }

    public function __invoke(DeliveryData $deliveryData): Delivery
    {
        $correlationId = $deliveryData->correlation_id ?? Str::uuid()->toString();

        $this->logger->info('Creating delivery', [
            'correlation_id' => $correlationId,
            'tenant_id' => $deliveryData->tenant_id,
            'order_id' => $deliveryData->order_id,
        ]);

        $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');

        return $this->db->transaction(function () use ($deliveryData, $correlationId) {
            $delivery = $this->deliveryRepository->create(
                new DeliveryData(
                    order_id: $deliveryData->order_id,
                    tenant_id: $deliveryData->tenant_id,
                    courier_id: null,
                    status: DeliveryStatus::PENDING,
                    from_address: $deliveryData->from_address,
                    to_address: $deliveryData->to_address,
                    payload: $deliveryData->payload,
                    correlation_id: $correlationId
                )
            );

            $route = $this->geoLogisticsService->calculateRoute(
                $delivery->from_address,
                $delivery->to_address,
                $correlationId
            );

            $delivery->route()->create([
                'route_data' => $route->route_data,
                'estimated_time' => $route->estimated_time,
                'distance' => $route->distance,
                'correlation_id' => $correlationId,
                'uuid' => Str::uuid()->toString(),
            ]);

            event(new DeliveryStatusChanged($delivery, $correlationId));

            return $delivery;
        });
    }
}
