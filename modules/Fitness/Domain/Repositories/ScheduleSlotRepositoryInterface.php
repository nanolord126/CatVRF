<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\Repositories;

use Carbon\CarbonImmutable;
use Modules\Fitness\Domain\Entities\ScheduleSlot;

interface ScheduleSlotRepositoryInterface
{
    public function findById(int $id): ?ScheduleSlot;

    public function findByTenantId(int $tenantId): array;

    public function findByVenueId(int $tenantId, int $venueId): array;

    public function findByTrainerId(int $tenantId, int $trainerId): array;

    public function findByDateRange(int $tenantId, CarbonImmutable $startDate, CarbonImmutable $endDate): array;

    public function findAvailableSlots(int $tenantId, CarbonImmutable $date): array;

    public function findActiveByDate(int $tenantId, CarbonImmutable $date): array;

    public function findRecurringSlots(int $tenantId): array;

    public function save(ScheduleSlot $slot): ScheduleSlot;

    public function delete(int $id): void;
}
