<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\Kids\Repositories;

use Modules\Fitness\Domain\Kids\Entities\KidsProgram;

interface KidsProgramRepositoryInterface
{
    public function save(KidsProgram $program): KidsProgram;

    public function findById(int $id): ?KidsProgram;

    public function findByTenantId(int $tenantId): array;

    public function findActiveByTenantId(int $tenantId): array;

    public function findByTargetAgeGroup(int $tenantId, string $ageGroup): array;

    public function delete(int $id): void;

    public function exists(int $id): bool;
}
