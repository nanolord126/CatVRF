<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Domain\Repositories;

use Illuminate\Support\Collection;

interface BlockedSlotRepositoryInterface
{
    public function findById(int $id): ?array;

    public function findByVenueId(int $venueId, \DateTimeImmutable $startDate, \DateTimeImmutable $endDate): Collection;

    public function findByMasterId(int $masterId, \DateTimeImmutable $startDate, \DateTimeImmutable $endDate): Collection;

    public function findOverlapping(
        ?int $masterId,
        \DateTimeImmutable $startTime,
        \DateTimeImmutable $endTime,
        ?int $excludeId = null
    ): Collection;

    public function save(array $slot): array;

    public function delete(int $id): bool;

    public function deleteByMasterAndDateRange(int $masterId, \DateTimeImmutable $start, \DateTimeImmutable $end): int;
}
