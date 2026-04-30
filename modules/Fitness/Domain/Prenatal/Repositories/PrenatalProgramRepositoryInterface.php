<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\Prenatal\Repositories;

use Modules\Fitness\Domain\Prenatal\Entities\PrenatalProgram;

interface PrenatalProgramRepositoryInterface
{
    public function save(PrenatalProgram $program): PrenatalProgram;

    public function findById(int $id): ?PrenatalProgram;

    public function findByTenantId(int $tenantId): array;

    public function findActiveByTenantId(int $tenantId): array;

    public function findByTargetTrimester(int $tenantId, string $trimester): array;

    public function delete(int $id): void;

    public function exists(int $id): bool;
}
