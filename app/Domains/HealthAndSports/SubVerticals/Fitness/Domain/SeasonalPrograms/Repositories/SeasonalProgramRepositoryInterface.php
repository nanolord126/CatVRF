<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\SeasonalPrograms\Repositories;

use Carbon\CarbonImmutable;
use Modules\Fitness\Domain\SeasonalPrograms\Entities\SeasonalProgram;

interface SeasonalProgramRepositoryInterface
{
    public function findById(int $id): ?SeasonalProgram;

    public function findByTenantId(int $tenantId): array;

    public function findByTenantIdAndStatus(int $tenantId, string $status): array;

    public function findActiveByTenantId(int $tenantId): array;

    public function findUpcomingByTenantId(int $tenantId): array;

    public function findOngoingByTenantId(int $tenantId): array;

    public function findByType(int $tenantId, string $type): array;

    public function save(SeasonalProgram $program): SeasonalProgram;

    public function delete(int $id): void;

    public function exists(int $id): bool;
}
