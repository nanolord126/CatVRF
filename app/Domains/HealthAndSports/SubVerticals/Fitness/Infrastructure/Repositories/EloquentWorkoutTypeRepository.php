<?php

declare(strict_types=1);

namespace Modules\Fitness\Infrastructure\Repositories;

use Modules\Fitness\Domain\Entities\WorkoutType;
use Modules\Fitness\Domain\Repositories\WorkoutTypeRepositoryInterface;
use Modules\Fitness\Infrastructure\Models\WorkoutTypeModel;

final class EloquentWorkoutTypeRepository implements WorkoutTypeRepositoryInterface
{
    public function findById(int $id): ?WorkoutType
    {
        $model = WorkoutTypeModel::find($id);
        return $model?->toDomain();
    }

    public function findByTenantId(int $tenantId): array
    {
        return WorkoutTypeModel::where('tenant_id', $tenantId)
            ->get()
            ->map(fn (WorkoutTypeModel $model) => $model->toDomain())
            ->toArray();
    }

    public function findActiveByTenantId(int $tenantId): array
    {
        return WorkoutTypeModel::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get()
            ->map(fn (WorkoutTypeModel $model) => $model->toDomain())
            ->toArray();
    }

    public function findByCategory(int $tenantId, string $category): array
    {
        return WorkoutTypeModel::where('tenant_id', $tenantId)
            ->where('category', $category)
            ->get()
            ->map(fn (WorkoutTypeModel $model) => $model->toDomain())
            ->toArray();
    }

    public function findByIntensity(int $tenantId, string $intensity): array
    {
        return WorkoutTypeModel::where('tenant_id', $tenantId)
            ->where('intensity', $intensity)
            ->get()
            ->map(fn (WorkoutTypeModel $model) => $model->toDomain())
            ->toArray();
    }

    public function findGroupWorkouts(int $tenantId): array
    {
        return WorkoutTypeModel::where('tenant_id', $tenantId)
            ->where('is_group', true)
            ->get()
            ->map(fn (WorkoutTypeModel $model) => $model->toDomain())
            ->toArray();
    }

    public function save(WorkoutType $workoutType): WorkoutType
    {
        $model = WorkoutTypeModel::fromDomain($workoutType);
        $model->save();
        return $model->toDomain();
    }

    public function delete(int $id): void
    {
        WorkoutTypeModel::destroy($id);
    }
}
