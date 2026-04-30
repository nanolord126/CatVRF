<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\Prenatal\Repositories;

use Modules\Fitness\Domain\Prenatal\Entities\PrenatalHealthProfile;

interface PrenatalHealthProfileRepositoryInterface
{
    public function save(PrenatalHealthProfile $profile): PrenatalHealthProfile;

    public function findById(int $id): ?PrenatalHealthProfile;

    public function findByClientId(int $clientId): ?PrenatalHealthProfile;

    public function findByTenantId(int $tenantId): array;

    public function delete(int $id): void;

    public function exists(int $id): bool;
}
