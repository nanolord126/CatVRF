<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\SeasonalPrograms\Repositories;

use Carbon\CarbonImmutable;
use Modules\Fitness\Domain\SeasonalPrograms\Entities\ProgramProgressLog;

interface ProgramProgressLogRepositoryInterface
{
    public function findById(int $id): ?ProgramProgressLog;

    public function findByEnrollmentId(int $enrollmentId): array;

    public function findByEnrollmentIdAndDate(int $enrollmentId, string $date): ?ProgramProgressLog;

    public function findByEnrollmentIdBetween(
        int $enrollmentId,
        string $startDate,
        string $endDate
    ): array;

    public function getLatestByEnrollmentId(int $enrollmentId): ?ProgramProgressLog;

    public function save(ProgramProgressLog $log): ProgramProgressLog;

    public function delete(int $id): void;

    public function exists(int $id): bool;
}
