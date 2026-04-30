<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\Repositories;

use Carbon\CarbonImmutable;
use Modules\Fitness\Domain\Entities\Attendance;

interface AttendanceRepositoryInterface
{
    public function findById(int $id): ?Attendance;

    public function findByClientId(int $clientId): array;

    public function findByBookingId(int $bookingId): ?Attendance;

    public function findByScheduleSlotId(int $scheduleSlotId): array;

    public function findByDateRange(int $tenantId, CarbonImmutable $startDate, CarbonImmutable $endDate): array;

    public function findByTrainerId(int $trainerId, CarbonImmutable $startDate, CarbonImmutable $endDate): array;

    public function save(Attendance $attendance): Attendance;

    public function delete(int $id): void;
}
