<?php

declare(strict_types=1);

namespace Modules\Loyalty\Domain\Repositories;

use Modules\Loyalty\Domain\Entities\LoyaltyProgram;
use Modules\Loyalty\Domain\Enums\VerticalType;

interface LoyaltyProgramRepositoryInterface
{
    public function findById(string $id): ?LoyaltyProgram;

    public function findByUuid(string $uuid): ?LoyaltyProgram;

    public function findByTenantAndVertical(int $tenantId, VerticalType $verticalType): array;

    public function findActiveByTenant(int $tenantId): array;

    public function save(LoyaltyProgram $program): LoyaltyProgram;

    public function delete(string $id): void;
}
