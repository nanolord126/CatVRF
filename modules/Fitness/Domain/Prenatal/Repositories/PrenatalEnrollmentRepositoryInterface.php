<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\Prenatal\Repositories;

use Modules\Fitness\Domain\Prenatal\Entities\PrenatalEnrollment;

interface PrenatalEnrollmentRepositoryInterface
{
    public function save(PrenatalEnrollment $enrollment): PrenatalEnrollment;

    public function findById(int $id): ?PrenatalEnrollment;

    public function findByClientId(int $clientId): array;

    public function findByProgramId(int $programId): array;

    public function findPendingClearance(int $tenantId): array;

    public function findActiveByClientId(int $clientId): array;

    public function delete(int $id): void;

    public function exists(int $id): bool;
}
