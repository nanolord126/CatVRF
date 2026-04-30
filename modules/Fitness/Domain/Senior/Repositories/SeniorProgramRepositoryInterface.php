<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\Senior\Repositories;

use Modules\Fitness\Domain\Senior\Entities\SeniorProgram;

interface SeniorProgramRepositoryInterface
{
    public function save(SeniorProgram $program): SeniorProgram;

    public function findById(int $id): ?SeniorProgram;

    public function findByTenantId(int $tenantId): array;

    public function findActiveByTenantId(int $tenantId): array;

    public function findByFocusArea(int $tenantId, string $focusArea): array;

    public function delete(int $id): void;

    public function exists(int $id): bool;
}
