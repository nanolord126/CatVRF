<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\Senior\Repositories;

use Modules\Fitness\Domain\Senior\Entities\SeniorHealthProfile;

interface SeniorHealthProfileRepositoryInterface
{
    public function save(SeniorHealthProfile $profile): SeniorHealthProfile;

    public function findById(int $id): ?SeniorHealthProfile;

    public function findByClientId(int $clientId): ?SeniorHealthProfile;

    public function findByTenantId(int $tenantId): array;

    public function delete(int $id): void;

    public function exists(int $id): bool;
}
