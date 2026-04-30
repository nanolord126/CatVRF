<?php

declare(strict_types=1);

namespace Modules\Fitness\Infrastructure\Repositories;

use Modules\Fitness\Domain\Entities\Trainer;
use Modules\Fitness\Domain\Repositories\TrainerRepositoryInterface;
use Modules\Fitness\Infrastructure\Models\TrainerModel;

final class EloquentTrainerRepository implements TrainerRepositoryInterface
{
    public function findById(int $id): ?Trainer
    {
        $model = TrainerModel::find($id);
        return $model?->toDomain();
    }

    public function findByUserId(int $userId): ?Trainer
    {
        $model = TrainerModel::where('user_id', $userId)->first();
        return $model?->toDomain();
    }

    public function findByTenantId(int $tenantId): array
    {
        return TrainerModel::where('tenant_id', $tenantId)
            ->get()
            ->map(fn (TrainerModel $model) => $model->toDomain())
            ->toArray();
    }

    public function findAvailableByTenantId(int $tenantId): array
    {
        return TrainerModel::where('tenant_id', $tenantId)
            ->where('is_available', true)
            ->get()
            ->map(fn (TrainerModel $model) => $model->toDomain())
            ->toArray();
    }

    public function findBySpecialization(int $tenantId, string $specialization): array
    {
        return TrainerModel::where('tenant_id', $tenantId)
            ->where('specialization', 'like', "%{$specialization}%")
            ->get()
            ->map(fn (TrainerModel $model) => $model->toDomain())
            ->toArray();
    }

    public function findByVenueId(int $tenantId, int $venueId): array
    {
        return TrainerModel::where('tenant_id', $tenantId)
            ->whereHas('scheduleSlots', fn ($q) => $q->where('venue_id', $venueId))
            ->get()
            ->map(fn (TrainerModel $model) => $model->toDomain())
            ->toArray();
    }

    public function findTopRated(int $tenantId, int $limit = 10): array
    {
        return TrainerModel::where('tenant_id', $tenantId)
            ->orderByDesc('rating')
            ->limit($limit)
            ->get()
            ->map(fn (TrainerModel $model) => $model->toDomain())
            ->toArray();
    }

    public function save(Trainer $trainer): Trainer
    {
        $model = TrainerModel::fromDomain($trainer);
        $model->save();
        return $model->toDomain();
    }

    public function delete(int $id): void
    {
        TrainerModel::destroy($id);
    }
}
