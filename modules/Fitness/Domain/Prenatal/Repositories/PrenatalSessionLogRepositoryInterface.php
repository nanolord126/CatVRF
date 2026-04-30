<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\Prenatal\Repositories;

use Modules\Fitness\Domain\Prenatal\Entities\PrenatalSessionLog;

interface PrenatalSessionLogRepositoryInterface
{
    public function save(PrenatalSessionLog $log): PrenatalSessionLog;

    public function findById(int $id): ?PrenatalSessionLog;

    public function findByEnrollmentId(int $enrollmentId): array;

    public function findByEnrollmentIdAndDate(int $enrollmentId, string $date): ?PrenatalSessionLog;

    public function findByEnrollmentIdAndDateRange(int $enrollmentId, string $startDate, string $endDate): array;

    public function delete(int $id): void;

    public function exists(int $id): bool;
}
