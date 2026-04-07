<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Application\B2C\UseCases;

use App\Domains\RealEstate\Application\B2C\DTOs\PropertyDTO;
use App\Domains\RealEstate\Domain\Repository\PropertyRepositoryInterface;
use App\Domains\RealEstate\Domain\ValueObjects\PropertyId;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Class GetPropertyDetailsUseCase
 *
 * Part of the RealEstate vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Component of the CatVRF platform.
 * Follows strict coding standards:
 * - final class (no inheritance unless required)
 * - private readonly properties
 * - Constructor injection only
 * - correlation_id in all operations
 *
 * @package App\Domains\RealEstate\Application\B2C\UseCases
 */
final class GetPropertyDetailsUseCase
{
    public function __construct(
        private readonly PropertyRepositoryInterface $propertyRepository,
        private readonly LoggerInterface             $logger) {}

    /**
     * @throws RuntimeException When property is not found or is inactive
     */
    public function handle(string $propertyId, string $correlationId): PropertyDTO
    {
        $this->logger->info('RealEstate.GetPropertyDetails started', [
            'correlation_id' => $correlationId,
            'property_id'    => $propertyId,
        ]);

        $property = $this->propertyRepository->findById(
            PropertyId::fromString($propertyId)
        );

        if ($property === null) {
            $this->logger->warning('RealEstate.GetPropertyDetails: not found', [
                'correlation_id' => $correlationId,
                'property_id'    => $propertyId,
            ]);

            throw new RuntimeException("Property {$propertyId} not found.");
        }

        if (! $property->isActive()) {
            throw new RuntimeException(
                "Property {$propertyId} is not available (status: {$property->getStatus()->value})."
            );
        }

        $dto = PropertyDTO::fromEntity($property);

        $this->logger->info('RealEstate.GetPropertyDetails completed', [
            'correlation_id' => $correlationId,
            'property_id'    => $propertyId,
            'title'          => $property->getTitle(),
        ]);

        return $dto;
    }
}
