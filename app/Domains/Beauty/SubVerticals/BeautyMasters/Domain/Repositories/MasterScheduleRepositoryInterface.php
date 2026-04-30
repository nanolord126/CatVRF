<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Domain\Repositories;

use Illuminate\Support\Collection;

interface MasterScheduleRepositoryInterface
{
    public function findByMasterId(int $masterId): Collection;

    public function findByMasterIdAndDay(int $masterId, string $dayOfWeek): ?array;

    public function findByVenueId(int $venueId): Collection;

    public function save(array $schedule): array;

    public function delete(int $id): bool;

    public function getWorkingHours(int $masterId, \DateTimeImmutable $date): ?array;

    public function isWorkingDay(int $masterId, \DateTimeImmutable $date): bool;
}
