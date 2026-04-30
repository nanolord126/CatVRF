<?php

declare(strict_types=1);

namespace Modules\RealEstate\Infrastructure\Repositories;

use Modules\RealEstate\Domain\Entities\Property;
use Modules\RealEstate\Domain\Repositories\PropertyRepositoryInterface;
use Modules\RealEstate\Domain\ValueObjects\PropertyId;

final class PropertyRepository implements PropertyRepositoryInterface
{
    public function save(Property $property): Property
    {
        // Placeholder implementation
        // In a real implementation, you would have a Property model and persist to database
        return $property;
    }

    public function findById(PropertyId $id): ?Property
    {
        // Placeholder implementation
        return null;
    }

    public function findByTenant(int $tenantId): array
    {
        // Placeholder implementation
        return [];
    }

    public function findByCity(string $city): array
    {
        // Placeholder implementation
        return [];
    }

    public function findByStatus(string $status): array
    {
        // Placeholder implementation
        return [];
    }

    public function delete(PropertyId $id): void
    {
        // Placeholder implementation
    }
}
