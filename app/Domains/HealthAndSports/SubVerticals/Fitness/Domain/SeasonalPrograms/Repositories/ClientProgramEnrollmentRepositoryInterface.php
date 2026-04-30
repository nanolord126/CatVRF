<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\SeasonalPrograms\Repositories;

use Modules\Fitness\Domain\SeasonalPrograms\Entities\ClientProgramEnrollment;

interface ClientProgramEnrollmentRepositoryInterface
{
    public function findById(int $id): ?ClientProgramEnrollment;

    public function findByClientId(int $clientId): array;

    public function findByProgramId(int $programId): array;

    public function findByProgramIdAndStatus(int $programId, string $status): array;

    public function findByClientIdAndProgramId(int $clientId, int $programId): ?ClientProgramEnrollment;

    public function findActiveByClientId(int $clientId): array;

    public function save(ClientProgramEnrollment $enrollment): ClientProgramEnrollment;

    public function delete(int $id): void;

    public function exists(int $id): bool;
}
