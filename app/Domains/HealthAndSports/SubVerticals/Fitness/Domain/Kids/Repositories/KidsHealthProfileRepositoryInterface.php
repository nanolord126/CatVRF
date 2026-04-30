<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\Kids\Repositories;

use Modules\Fitness\Domain\Kids\Entities\KidsHealthProfile;

interface KidsHealthProfileRepositoryInterface
{
    public function save(KidsHealthProfile $profile): KidsHealthProfile;

    public function findById(int $id): ?KidsHealthProfile;

    public function findByClientId(int $clientId): ?KidsHealthProfile;

    public function findByTenantId(int $tenantId): array;

    public function findByAgeGroup(int $tenantId, string $ageGroup): array;

    public function delete(int $id): void;

    public function exists(int $id): bool;
}
