<?php

declare(strict_types=1);

namespace Modules\RealEstate\Domain\Repositories;

use Modules\RealEstate\Domain\Entities\Property;
use Modules\RealEstate\Domain\ValueObjects\PropertyId;

interface PropertyRepositoryInterface
{
    public function save(Property $property): Property;

    public function findById(PropertyId $id): ?Property;

    public function findByTenant(int $tenantId): array;

    public function findByCity(string $city): array;

    public function findByStatus(string $status): array;

    public function delete(PropertyId $id): void;
}
