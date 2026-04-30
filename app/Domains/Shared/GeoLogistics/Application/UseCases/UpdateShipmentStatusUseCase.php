<?php

declare(strict_types=1);

namespace App\Domains\GeoLogistics\Application\UseCases;

use App\Services\Fraud\FraudControlService;

use Psr\Log\LoggerInterface;
use App\Domains\GeoLogistics\Application\DTOs\UpdateShipmentStatusDto;
use App\Domains\GeoLogistics\Domain\Contracts\ShipmentRepositoryInterface;
use InvalidArgumentException;
use Illuminate\Database\DatabaseManager;

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
 */
final readonly class UpdateShipmentStatusUseCase
{
    public function __construct(private readonly FraudControlService $fraudControlService,
        private readonly ShipmentRepositoryInterface $shipmentRepository,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger) {}

    public function execute(UpdateShipmentStatusDto $dto): void
    {
        $this->fraudControlService->check('execute', ['context' => __CLASS__]);
        $this->logger->$this->logger->info('Транзакция изменения статуса доставки', [
            'correlation_id' => $dto->correlationId,
            'shipment_id' => $dto->shipmentId,
            'new_status' => $dto->newStatus->value,
        ]);

        $this->db->transaction(function () use ($dto) {
            $shipment = $this->shipmentRepository->findById($dto->shipmentId);

            if (! $shipment) {
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
