<?php

declare(strict_types=1);

namespace Modules\Fitness\Infrastructure\Repositories;

use Carbon\CarbonImmutable;
use Modules\Fitness\Domain\Entities\WorkoutSession;
use Modules\Fitness\Domain\Repositories\WorkoutSessionRepositoryInterface;
use Modules\Fitness\Infrastructure\Models\WorkoutSessionModel;

final class EloquentWorkoutSessionRepository implements WorkoutSessionRepositoryInterface
{
    public function findById(int $id): ?WorkoutSession
    {
        $model = WorkoutSessionModel::find($id);
        return $model?->toDomain();
    }

    public function findByScheduleSlotId(int $scheduleSlotId): ?WorkoutSession
    {
        $model = WorkoutSessionModel::where('schedule_slot_id', $scheduleSlotId)->first();
        return $model?->toDomain();
    }

    public function findByTrainerId(int $trainerId, CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        return WorkoutSessionModel::where('trainer_id', $trainerId)
            ->whereBetween('start_time', [$startDate, $endDate])
            ->orderBy('start_time')
            ->get()
            ->map(fn (WorkoutSessionModel $model) => $model->toDomain())
            ->toArray();
    }

    public function findByVenueId(int $venueId, CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        return WorkoutSessionModel::where('venue_id', $venueId)
            ->whereBetween('start_time', [$startDate, $endDate])
            ->orderBy('start_time')
            ->get()
            ->map(fn (WorkoutSessionModel $model) => $model->toDomain())
            ->toArray();
    }

    public function findByDateRange(int $tenantId, CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        return WorkoutSessionModel::where('tenant_id', $tenantId)
            ->whereBetween('start_time', [$startDate, $endDate])
            ->orderBy('start_time')
            ->get()
            ->map(fn (WorkoutSessionModel $model) => $model->toDomain())
            ->toArray();
    }

    public function save(WorkoutSession $session): WorkoutSession
    {
        $model = WorkoutSessionModel::fromDomain($session);
        $model->save();
        return $model->toDomain();
    }

    public function delete(int $id): void
    {
        WorkoutSessionModel::destroy($id);
    }
}
