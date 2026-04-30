<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\Repositories;

use Carbon\CarbonImmutable;
use Modules\Fitness\Domain\Entities\Booking;

interface BookingRepositoryInterface
{
    public function findById(int $id): ?Booking;

    public function findByClientId(int $clientId): array;

    public function findByScheduleSlotId(int $scheduleSlotId): array;

    public function findByTenantId(int $tenantId): array;

    public function findByDateRange(int $tenantId, CarbonImmutable $startDate, CarbonImmutable $endDate): array;

    public function findUpcomingByClientId(int $clientId, int $limit = 10): array;

    public function findWaitlistBySlot(int $scheduleSlotId): array;

    public function save(Booking $booking): Booking;

    public function delete(int $id): void;
}
