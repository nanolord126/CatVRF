<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Application\B2C\UseCases;

use App\Domains\RealEstate\Application\B2C\DTOs\RequestViewingDTO;
use App\Domains\RealEstate\Domain\Entities\ViewingAppointment;
use App\Domains\RealEstate\Domain\Repository\PropertyRepositoryInterface;
use App\Domains\RealEstate\Domain\Repository\ViewingRepositoryInterface;
use App\Domains\RealEstate\Domain\ValueObjects\AgentId;
use App\Domains\RealEstate\Domain\ValueObjects\PropertyId;
use App\Domains\RealEstate\Domain\ValueObjects\ViewingId;
use App\Services\FraudControlService;
use Illuminate\Database\ConnectionInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

final class RequestViewingUseCase
{
    public function __construct(
        private readonly PropertyRepositoryInterface $propertyRepository,
        private readonly ViewingRepositoryInterface  $viewingRepository,
        private readonly FraudControlService         $fraud,
        private readonly ConnectionInterface         $db,
        private readonly LoggerInterface             $logger) {}

    /**
     * Client requests a viewing appointment on an active property.
     *
     * @throws RuntimeException
     */
    public function handle(RequestViewingDTO $dto): ViewingAppointment
    {
        $this->fraud->check(
            userId:            $dto->clientId,
            operationType:     'real_estate.viewing.request',
            amount:            0,
            ipAddress:         $dto->ipAddress,
            deviceFingerprint: $dto->deviceFingerprint,
            correlationId:     $dto->correlationId,
        );

        $this->logger->info('RealEstate.RequestViewing started', [
            'correlation_id' => $dto->correlationId,
            'property_id'    => $dto->propertyId,
            'client_id'      => $dto->clientId,
        ]);

        $property = $this->propertyRepository->findById(
            PropertyId::fromString($dto->propertyId)
        );

        if ($property === null || ! $property->isActive()) {
            throw new RuntimeException(
                "Property {$dto->propertyId} is not available for viewing."
            );
        }

        $hasConflict = $this->viewingRepository->hasConflict(
            propertyId: $property->getId(),
            scheduledAt: $dto->scheduledAt,
        );

        if ($hasConflict) {
            throw new RuntimeException(
                'The requested time slot is already taken. Please choose another time.'
            );
        }

        $viewingId = ViewingId::generate();

        $viewing = new ViewingAppointment(
            id: $viewingId,
            propertyId: $property->getId(),
            agentId: $property->getAgentId(),
            clientId: $dto->clientId,
            tenantId: $property->getTenantId(),
            scheduledAt: $dto->scheduledAt,
            clientName: $dto->clientName,
            clientPhone: $dto->clientPhone,
            notes: $dto->notes,
            correlationId: $dto->correlationId,
        );

        $this->db->transaction(function () use ($viewing): void {
            $this->viewingRepository->save($viewing);
        });

        $this->logger->info('RealEstate.RequestViewing completed', [
            'correlation_id' => $dto->correlationId,
            'viewing_id'     => $viewingId->getValue(),
            'property_id'    => $dto->propertyId,
            'client_id'      => $dto->clientId,
            'scheduled_at'   => $dto->scheduledAt->format('Y-m-d H:i'),
        ]);

        return $viewing;
    }
}
