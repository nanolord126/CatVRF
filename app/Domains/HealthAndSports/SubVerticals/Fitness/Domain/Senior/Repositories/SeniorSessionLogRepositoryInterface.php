<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\Senior\Repositories;

use Modules\Fitness\Domain\Senior\Entities\SeniorSessionLog;

interface SeniorSessionLogRepositoryInterface
{
    public function save(SeniorSessionLog $log): SeniorSessionLog;

    public function findById(int $id): ?SeniorSessionLog;

    public function findByEnrollmentId(int $enrollmentId): array;

    public function findByEnrollmentIdAndDate(int $enrollmentId, string $date): ?SeniorSessionLog;

    public function findByEnrollmentIdAndDateRange(int $enrollmentId, string $startDate, string $endDate): array;

    public function delete(int $id): void;

    public function exists(int $id): bool;
}
