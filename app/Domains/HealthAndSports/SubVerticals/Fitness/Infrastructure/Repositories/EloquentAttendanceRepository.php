<?php

declare(strict_types=1);

namespace Modules\Fitness\Infrastructure\Repositories;

use Carbon\CarbonImmutable;
use Modules\Fitness\Domain\Entities\Attendance;
use Modules\Fitness\Domain\Repositories\AttendanceRepositoryInterface;
use Modules\Fitness\Infrastructure\Models\AttendanceModel;

final class EloquentAttendanceRepository implements AttendanceRepositoryInterface
{
    public function findById(int $id): ?Attendance
    {
        $model = AttendanceModel::find($id);
        return $model?->toDomain();
    }

    public function findByClientId(int $clientId): array
    {
        return AttendanceModel::where('client_id', $clientId)
            ->orderByDesc('check_in_time')
            ->get()
            ->map(fn (AttendanceModel $model) => $model->toDomain())
            ->toArray();
    }

    public function findByBookingId(int $bookingId): ?Attendance
    {
        $model = AttendanceModel::where('booking_id', $bookingId)->first();
        return $model?->toDomain();
    }

    public function findByScheduleSlotId(int $scheduleSlotId): array
    {
        return AttendanceModel::where('schedule_slot_id', $scheduleSlotId)
            ->get()
            ->map(fn (AttendanceModel $model) => $model->toDomain())
            ->toArray();
    }

    public function findByDateRange(int $tenantId, CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        return AttendanceModel::where('tenant_id', $tenantId)
            ->whereBetween('check_in_time', [$startDate, $endDate])
            ->orderBy('check_in_time')
            ->get()
            ->map(fn (AttendanceModel $model) => $model->toDomain())
            ->toArray();
    }

    public function findByTrainerId(int $trainerId, CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        return AttendanceModel::where('trainer_id', $trainerId)
            ->whereBetween('check_in_time', [$startDate, $endDate])
            ->orderBy('check_in_time')
            ->get()
            ->map(fn (AttendanceModel $model) => $model->toDomain())
            ->toArray();
    }

    public function save(Attendance $attendance): Attendance
    {
        $model = AttendanceModel::fromDomain($attendance);
        $model->save();
        return $model->toDomain();
    }

    public function delete(int $id): void
    {
        AttendanceModel::destroy($id);
    }
}
