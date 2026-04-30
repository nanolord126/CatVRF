<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\Repositories;

use Modules\Fitness\Domain\Entities\Venue;

interface VenueRepositoryInterface
{
    public function findById(int $id): ?Venue;

    public function findByTenantId(int $tenantId): array;

    public function findActiveByTenantId(int $tenantId): array;

    public function findByCity(int $tenantId, string $city): array;

    public function save(Venue $venue): Venue;

    public function delete(int $id): void;
}
