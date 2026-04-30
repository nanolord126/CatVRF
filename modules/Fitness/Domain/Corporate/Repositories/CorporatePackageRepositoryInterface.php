<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\Corporate\Repositories;

use Modules\Fitness\Domain\Corporate\Entities\CorporatePackage;

interface CorporatePackageRepositoryInterface
{
    public function save(CorporatePackage $package): CorporatePackage;

    public function findById(int $id): ?CorporatePackage;

    public function findByTenantId(int $tenantId): array;

    public function findActiveByTenantId(int $tenantId): array;

    public function delete(int $id): void;

    public function exists(int $id): bool;
}
