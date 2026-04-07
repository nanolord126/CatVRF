<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Application\B2B\UseCases;

use App\Domains\RealEstate\Application\B2B\DTOs\CreatePropertyDTO;
use App\Domains\RealEstate\Domain\Entities\Property;
use App\Domains\RealEstate\Domain\Repository\AgentRepositoryInterface;
use App\Domains\RealEstate\Domain\Repository\PropertyRepositoryInterface;
use App\Domains\RealEstate\Domain\ValueObjects\AgentId;
use App\Domains\RealEstate\Domain\ValueObjects\Area;
use App\Domains\RealEstate\Domain\ValueObjects\Coordinate;
use App\Domains\RealEstate\Domain\ValueObjects\Price;
use App\Domains\RealEstate\Domain\ValueObjects\PropertyId;
use App\Services\FraudControlService;
use Illuminate\Database\ConnectionInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

final class CreatePropertyUseCase
{
    public function __construct(
        private readonly PropertyRepositoryInterface $propertyRepository,
        private readonly AgentRepositoryInterface    $agentRepository,
        private readonly FraudControlService         $fraud,
        private readonly ConnectionInterface         $db,
        private readonly LoggerInterface             $logger) {}

    /**
     * Create a new property listing in Draft status.
     *
     * @throws RuntimeException
     */
    public function handle(CreatePropertyDTO $dto): Property
    {
        $this->fraud->check(
            userId: $dto->agentUserId,
            operationType: 'real_estate.property.create',
            amount: $dto->priceKopecks,
            ipAddress: $dto->ipAddress,
            deviceFingerprint: $dto->deviceFingerprint,
            correlationId: $dto->correlationId,
        );

        $this->logger->info('RealEstate.CreateProperty started', [
            'correlation_id' => $dto->correlationId,
            'tenant_id'      => $dto->tenantId,
            'agent_id'       => $dto->agentId,
        ]);

        $agent = $this->agentRepository->findByIdAndTenant(
            AgentId::fromString($dto->agentId),
            $dto->tenantId,
        );

        if ($agent === null || ! $agent->isActive()) {
            throw new RuntimeException("Agent {$dto->agentId} not found or inactive.");
        }

        $propertyId = PropertyId::generate();

        $property = new Property(
            id: $propertyId,
            agentId: AgentId::fromString($dto->agentId),
            tenantId: $dto->tenantId,
            title: $dto->title,
            description: $dto->description,
            address: $dto->address,
            coordinates: new Coordinate($dto->lat, $dto->lon),
            type: $dto->type,
            price: Price::fromKopecks($dto->priceKopecks),
            area: Area::fromFloat($dto->areaSqm),
            rooms: $dto->rooms,
            floor: $dto->floor,
            totalFloors: $dto->totalFloors,
            correlationId: $dto->correlationId,
        );

        foreach ($dto->photos as $photo) {
            $property->addPhoto(
                url: (string) ($photo['url'] ?? ''),
                caption: (string) ($photo['caption'] ?? ''),
            );
        }

        foreach ($dto->documents as $doc) {
            $property->addDocument(
                url: (string) ($doc['url'] ?? ''),
                type: (string) ($doc['type'] ?? 'other'),
                name: (string) ($doc['name'] ?? ''),
            );
        }

        $this->db->transaction(function () use ($property, $agent): void {
            $this->propertyRepository->save($property);
            $agent->assignProperty($property->getId());
            $this->agentRepository->save($agent);
        });

        $this->logger->info('RealEstate.CreateProperty completed', [
            'correlation_id' => $dto->correlationId,
            'property_id'    => $propertyId->getValue(),
            'title'          => $dto->title,
            'tenant_id'      => $dto->tenantId,
        ]);

        return $property;
    }
}
