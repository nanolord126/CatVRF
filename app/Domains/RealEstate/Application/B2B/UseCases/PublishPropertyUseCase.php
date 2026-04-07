<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Application\B2B\UseCases;

use App\Domains\RealEstate\Domain\Repository\PropertyRepositoryInterface;
use App\Domains\RealEstate\Domain\ValueObjects\PropertyId;
use App\Services\FraudControlService;
use Illuminate\Database\ConnectionInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

final class PublishPropertyUseCase
{
    public function __construct(
        private readonly PropertyRepositoryInterface $propertyRepository,
        private readonly FraudControlService         $fraud,
        private readonly ConnectionInterface         $db,
        private readonly LoggerInterface             $logger) {}

    /**
     * Publishes a Draft property — changes status to Active, fires PropertyListed event.
     *
     * @throws RuntimeException
     */
    public function handle(
        string $propertyId,
        int    $tenantId,
        string $correlationId,
    ): void {
        $this->fraud->check(
            userId: (int) $tenantId,
            operationType: 'real_estate.property.publish',
            amount: 0,
            ipAddress: null,
            deviceFingerprint: null,
            correlationId: $correlationId,
        );

        $this->logger->info('RealEstate.PublishProperty started', [
            'correlation_id' => $correlationId,
            'property_id'    => $propertyId,
            'tenant_id'      => $tenantId,
        ]);

        $property = $this->propertyRepository->findByIdAndTenant(
            PropertyId::fromString($propertyId),
            $tenantId,
        );

        if ($property === null) {
            throw new RuntimeException("Property {$propertyId} not found.");
        }

        $property->publish($correlationId);

        $events = $property->pullDomainEvents();

        $this->db->transaction(function () use ($property): void {
            $this->propertyRepository->save($property);
        });

        foreach ($events as $event) {
            event($event);
        }

        $this->logger->info('RealEstate.PublishProperty completed', [
            'correlation_id' => $correlationId,
            'property_id'    => $propertyId,
        ]);
    }
}
