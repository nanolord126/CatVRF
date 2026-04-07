<?php

declare(strict_types=1);

namespace App\Domains\GeoLogistics\Application\UseCases;


use Psr\Log\LoggerInterface;
use App\Domains\GeoLogistics\Application\DTOs\UpdateShipmentStatusDto;
use App\Domains\GeoLogistics\Domain\Contracts\ShipmentRepositoryInterface;
use InvalidArgumentException;
use App\Services\FraudControlService;

/**
 * Class UpdateShipmentStatusUseCase
 *
 * Part of the GeoLogistics vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Component of the CatVRF platform.
 * Follows strict coding standards:
 * - final class (no inheritance unless required)
 * - private readonly properties
 * - Constructor injection only
 * - correlation_id in all operations
 *
 * @package App\Domains\GeoLogistics\Application\UseCases
 */
final readonly class UpdateShipmentStatusUseCase
{
    public function __construct(private ShipmentRepositoryInterface $shipmentRepository,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}

    public function execute(UpdateShipmentStatusDto $dto): void
    {
        $this->logger->info('Транзакция изменения статуса доставки', [
            'correlation_id' => $dto->correlationId,
            'shipment_id' => $dto->shipmentId,
            'new_status' => $dto->newStatus->value,
        ]);

        $this->db->transaction(function () use ($dto) {
            $shipment = $this->shipmentRepository->findById($dto->shipmentId);
            
            if (!$shipment) {
                throw new InvalidArgumentException("Shipment [{$dto->shipmentId}] not found.");
            }

            // Блокируем для консистентности потоков
            // Используется query(), т.к. where id...
            $shipment->newQuery()->where('id', $shipment->id)->lockForUpdate()->first();

            $shipment->transitionTo($dto->newStatus, $dto->correlationId);
            $this->shipmentRepository->save($shipment);
        });
    }
}
