<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\Corporate\Repositories;

use Modules\Fitness\Domain\Corporate\Entities\CorporateEnrollment;

interface CorporateEnrollmentRepositoryInterface
{
    public function save(CorporateEnrollment $enrollment): CorporateEnrollment;

    public function findById(int $id): ?CorporateEnrollment;

    public function findByTenantId(int $tenantId): array;

    public function findByCorporateClientId(int $corporateClientId): array;

    public function findByClientId(int $clientId): array;

    public function findActiveByTenantId(int $tenantId): array;

    public function findExpiringSoon(int $tenantId, int $daysThreshold = 30): array;

    public function delete(int $id): void;

    public function exists(int $id): bool;
}
