<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\Repositories;

use Modules\Fitness\Domain\Entities\Trainer;

interface TrainerRepositoryInterface
{
    public function findById(int $id): ?Trainer;

    public function findByUserId(int $userId): ?Trainer;

    public function findByTenantId(int $tenantId): array;

    public function findAvailableByTenantId(int $tenantId): array;

    public function findBySpecialization(int $tenantId, string $specialization): array;

    public function findByVenueId(int $tenantId, int $venueId): array;

    public function findTopRated(int $tenantId, int $limit = 10): array;

    public function save(Trainer $trainer): Trainer;

    public function delete(int $id): void;
}
