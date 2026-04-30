<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\Kids\Repositories;

use Modules\Fitness\Domain\Kids\Entities\KidsProgramEnrollment;

interface KidsProgramEnrollmentRepositoryInterface
{
    public function save(KidsProgramEnrollment $enrollment): KidsProgramEnrollment;

    public function findById(int $id): ?KidsProgramEnrollment;

    public function findByClientId(int $clientId): array;

    public function findByProgramId(int $programId): array;

    public function findPendingClearance(int $tenantId): array;

    public function findActiveByClientId(int $clientId): array;

    public function delete(int $id): void;

    public function exists(int $id): bool;
}
