<?php

declare(strict_types=1);

namespace Modules\Fitness\Infrastructure\Repositories;

use Carbon\CarbonImmutable;
use Modules\Fitness\Domain\Entities\ScheduleSlot;
use Modules\Fitness\Domain\Repositories\ScheduleSlotRepositoryInterface;
use Modules\Fitness\Infrastructure\Models\ScheduleSlotModel;

final class EloquentScheduleSlotRepository implements ScheduleSlotRepositoryInterface
{
    public function findById(int $id): ?ScheduleSlot
    {
        $model = ScheduleSlotModel::find($id);
        return $model?->toDomain();
    }

    public function findByTenantId(int $tenantId): array
    {
        return ScheduleSlotModel::where('tenant_id', $tenantId)
            ->orderBy('start_time')
            ->get()
            ->map(fn (ScheduleSlotModel $model) => $model->toDomain())
            ->toArray();
    }

    public function findByVenueId(int $tenantId, int $venueId): array
    {
        return ScheduleSlotModel::where('tenant_id', $tenantId)
            ->where('venue_id', $venueId)
            ->where('start_time', '>=', CarbonImmutable::now())
            ->orderBy('start_time')
            ->get()
            ->map(fn (ScheduleSlotModel $model) => $model->toDomain())
            ->toArray();
    }

    public function findByTrainerId(int $tenantId, int $trainerId): array
    {
        return ScheduleSlotModel::where('tenant_id', $tenantId)
            ->where('trainer_id', $trainerId)
            ->where('start_time', '>=', CarbonImmutable::now())
            ->orderBy('start_time')
            ->get()
            ->map(fn (ScheduleSlotModel $model) => $model->toDomain())
            ->toArray();
    }

    public function findByDateRange(int $tenantId, CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        return ScheduleSlotModel::where('tenant_id', $tenantId)
            ->whereBetween('start_time', [$startDate, $endDate])
            ->orderBy('start_time')
            ->get()
            ->map(fn (ScheduleSlotModel $model) => $model->toDomain())
            ->toArray();
    }

    public function findAvailableSlots(int $tenantId, CarbonImmutable $date): array
    {
        return ScheduleSlotModel::where('tenant_id', $tenantId)
            ->whereDate('start_time', $date)
            ->where('is_active', true)
            ->whereRaw('booked_count < capacity')
            ->orderBy('start_time')
            ->get()
            ->map(fn (ScheduleSlotModel $model) => $model->toDomain())
            ->toArray();
    }

    public function findActiveByDate(int $tenantId, CarbonImmutable $date): array
    {
        return ScheduleSlotModel::where('tenant_id', $tenantId)
            ->whereDate('start_time', $date)
            ->where('is_active', true)
            ->orderBy('start_time')
            ->get()
            ->map(fn (ScheduleSlotModel $model) => $model->toDomain())
            ->toArray();
    }

    public function findRecurringSlots(int $tenantId): array
    {
        return ScheduleSlotModel::where('tenant_id', $tenantId)
            ->where('is_recurring', true)
            ->where('is_active', true)
            ->get()
            ->map(fn (ScheduleSlotModel $model) => $model->toDomain())
            ->toArray();
    }

    public function save(ScheduleSlot $slot): ScheduleSlot
    {
        $model = ScheduleSlotModel::fromDomain($slot);
        $model->save();
        return $model->toDomain();
    }

    public function delete(int $id): void
    {
        ScheduleSlotModel::destroy($id);
    }
}
