<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\Senior\Repositories;

use Modules\Fitness\Domain\Senior\Entities\SeniorProgramEnrollment;

interface SeniorProgramEnrollmentRepositoryInterface
{
    public function save(SeniorProgramEnrollment $enrollment): SeniorProgramEnrollment;

    public function findById(int $id): ?SeniorProgramEnrollment;

    public function findByClientId(int $clientId): array;

    public function findByProgramId(int $programId): array;

    public function findPendingClearance(int $tenantId): array;

    public function findActiveByClientId(int $clientId): array;

    public function delete(int $id): void;

    public function exists(int $id): bool;
}
