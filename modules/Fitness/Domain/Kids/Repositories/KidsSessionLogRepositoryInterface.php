<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\Kids\Repositories;

use Modules\Fitness\Domain\Kids\Entities\KidsSessionLog;

interface KidsSessionLogRepositoryInterface
{
    public function save(KidsSessionLog $log): KidsSessionLog;

    public function findById(int $id): ?KidsSessionLog;

    public function findByEnrollmentId(int $enrollmentId): array;

    public function findByEnrollmentIdAndDate(int $enrollmentId, string $date): ?KidsSessionLog;

    public function findByEnrollmentIdAndDateRange(int $enrollmentId, string $startDate, string $endDate): array;

    public function delete(int $id): void;

    public function exists(int $id): bool;
}
