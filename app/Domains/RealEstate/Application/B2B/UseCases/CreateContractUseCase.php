<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Application\B2B\UseCases;

use App\Domains\RealEstate\Application\B2B\DTOs\CreateContractDTO;
use App\Domains\RealEstate\Domain\Entities\Contract;
use App\Domains\RealEstate\Domain\Repository\AgentRepositoryInterface;
use App\Domains\RealEstate\Domain\Repository\ContractRepositoryInterface;
use App\Domains\RealEstate\Domain\Repository\PropertyRepositoryInterface;
use App\Domains\RealEstate\Domain\ValueObjects\AgentId;
use App\Domains\RealEstate\Domain\ValueObjects\ContractId;
use App\Domains\RealEstate\Domain\ValueObjects\Price;
use App\Domains\RealEstate\Domain\ValueObjects\PropertyId;
use App\Services\FraudControlService;
use Illuminate\Database\ConnectionInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

final class CreateContractUseCase
{
    public function __construct(
        private readonly ContractRepositoryInterface  $contractRepository,
        private readonly PropertyRepositoryInterface  $propertyRepository,
        private readonly AgentRepositoryInterface     $agentRepository,
        private readonly FraudControlService          $fraud,
        private readonly ConnectionInterface          $db,
        private readonly LoggerInterface              $logger) {}

    /**
     * Creates a pending real-estate contract (not yet signed).
     *
     * @throws RuntimeException
     */
    public function handle(CreateContractDTO $dto): Contract
    {
        $this->fraud->check(
            userId: $dto->agentUserId,
            operationType: 'real_estate.contract.create',
            amount: $dto->priceKopecks,
            ipAddress: $dto->ipAddress,
            deviceFingerprint: $dto->deviceFingerprint,
            correlationId: $dto->correlationId,
        );

        $this->logger->info('RealEstate.CreateContract started', [
            'correlation_id' => $dto->correlationId,
            'tenant_id'      => $dto->tenantId,
            'property_id'    => $dto->propertyId,
            'client_id'      => $dto->clientId,
            'type'           => $dto->type->value,
        ]);

        $property = $this->propertyRepository->findByIdAndTenant(
            PropertyId::fromString($dto->propertyId),
            $dto->tenantId,
        );

        if ($property === null || ! $property->isActive()) {
            throw new RuntimeException(
                "Property {$dto->propertyId} is not available for contracting."
            );
        }

        $agent = $this->agentRepository->findByIdAndTenant(
            AgentId::fromString($dto->agentId),
            $dto->tenantId,
        );

        if ($agent === null) {
            throw new RuntimeException("Agent {$dto->agentId} not found.");
        }

        $contractId = ContractId::generate();

        $contract = new Contract(
            id: $contractId,
            propertyId: $property->getId(),
            agentId: $agent->getId(),
            clientId: $dto->clientId,
            tenantId: $dto->tenantId,
            type: $dto->type,
            price: Price::fromKopecks($dto->priceKopecks),
            correlationId: $dto->correlationId,
            documentUrl: $dto->documentUrl,
            leaseDurationMonths: $dto->leaseDurationMonths,
        );

        $this->db->transaction(function () use ($contract): void {
            $this->contractRepository->save($contract);
        });

        $this->logger->info('RealEstate.CreateContract completed', [
            'correlation_id' => $dto->correlationId,
            'contract_id'    => $contractId->getValue(),
            'property_id'    => $dto->propertyId,
        ]);

        return $contract;
    }
}
