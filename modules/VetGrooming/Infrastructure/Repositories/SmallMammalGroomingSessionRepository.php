<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Infrastructure\Repositories;

use Modules\VetGrooming\Domain\Entities\SmallMammalGroomingSession;
use Modules\VetGrooming\Domain\Repositories\SmallMammalGroomingSessionRepositoryInterface;
use Modules\VetGrooming\Infrastructure\Models\SmallMammalGroomingSessionModel;
use Carbon\CarbonImmutable;

final class SmallMammalGroomingSessionRepository implements SmallMammalGroomingSessionRepositoryInterface
{
    public function findById(int $id): ?SmallMammalGroomingSession
    {
        $model = SmallMammalGroomingSessionModel::find($id);
        return $model?->toDomain();
    }

    public function findByPetId(int $petId): array
    {
        $models = SmallMammalGroomingSessionModel::where('pet_id', $petId)->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findByGroomerId(int $groomerId, int $limit = 100): array
    {
        $models = SmallMammalGroomingSessionModel::where('groomer_id', $groomerId)
            ->limit($limit)
            ->latest('started_at')
            ->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findByTenant(int $tenantId, int $limit = 100): array
    {
        $models = SmallMammalGroomingSessionModel::where('tenant_id', $tenantId)
            ->limit($limit)
            ->latest('started_at')
            ->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findByMammalGroup(string $mammalGroup, int $tenantId, int $limit = 100): array
    {
        $models = SmallMammalGroomingSessionModel::where('tenant_id', $tenantId)
            ->where('mammal_group', $mammalGroup)
            ->limit($limit)
            ->latest('started_at')
            ->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findHighStressSessions(int $tenantId, int $limit = 50): array
    {
        $models = SmallMammalGroomingSessionModel::where('tenant_id', $tenantId)
            ->where('stress_level_after', '>=', 7)
            ->limit($limit)
            ->latest('started_at')
            ->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findWithSedation(int $tenantId, int $limit = 50): array
    {
        $models = SmallMammalGroomingSessionModel::where('tenant_id', $tenantId)
            ->where('sedation_used', true)
            ->limit($limit)
            ->latest('started_at')
            ->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findByDateRange(int $tenantId, CarbonImmutable $start, CarbonImmutable $end, int $limit = 100): array
    {
        $models = SmallMammalGroomingSessionModel::where('tenant_id', $tenantId)
            ->whereBetween('started_at', [$start, $end])
            ->limit($limit)
            ->latest('started_at')
            ->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findIncompleteByGroomer(int $groomerId): ?SmallMammalGroomingSession
    {
        $model = SmallMammalGroomingSessionModel::where('groomer_id', $groomerId)
            ->where('status', 'in_progress')
            ->first();
        return $model?->toDomain();
    }

    public function save(SmallMammalGroomingSession $session): SmallMammalGroomingSession
    {
        $model = SmallMammalGroomingSessionModel::fromDomain($session);
        $model->save();
        return $model->toDomain();
    }

    public function delete(int $id): void
    {
        SmallMammalGroomingSessionModel::destroy($id);
    }
}
