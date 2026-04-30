<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Infrastructure\Repositories;

use Modules\VetGrooming\Domain\Entities\LargeMammalGroomingSession;
use Modules\VetGrooming\Domain\Repositories\LargeMammalGroomingSessionRepositoryInterface;
use Modules\VetGrooming\Infrastructure\Models\LargeMammalGroomingSessionModel;
use Carbon\CarbonImmutable;

final class LargeMammalGroomingSessionRepository implements LargeMammalGroomingSessionRepositoryInterface
{
    public function findById(int $id): ?LargeMammalGroomingSession
    {
        $model = LargeMammalGroomingSessionModel::find($id);
        return $model?->toDomain();
    }

    public function findByPetId(int $petId): array
    {
        $models = LargeMammalGroomingSessionModel::where('pet_id', $petId)->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findByGroomerId(int $groomerId, int $limit = 100): array
    {
        $models = LargeMammalGroomingSessionModel::where('groomer_id', $groomerId)
            ->limit($limit)
            ->latest('started_at')
            ->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findByTenant(int $tenantId, int $limit = 100): array
    {
        $models = LargeMammalGroomingSessionModel::where('tenant_id', $tenantId)
            ->limit($limit)
            ->latest('started_at')
            ->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findByMammalGroup(string $mammalGroup, int $tenantId, int $limit = 100): array
    {
        $models = LargeMammalGroomingSessionModel::where('tenant_id', $tenantId)
            ->where('mammal_group', $mammalGroup)
            ->limit($limit)
            ->latest('started_at')
            ->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findHighAggressionSessions(int $tenantId, int $limit = 50): array
    {
        $models = LargeMammalGroomingSessionModel::where('tenant_id', $tenantId)
            ->where('aggression_level', '>=', 7)
            ->limit($limit)
            ->latest('started_at')
            ->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findWithSafetyIncidents(int $tenantId, int $limit = 50): array
    {
        $models = LargeMammalGroomingSessionModel::where('tenant_id', $tenantId)
            ->where('safety_incident', true)
            ->limit($limit)
            ->latest('started_at')
            ->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findByDateRange(int $tenantId, CarbonImmutable $start, CarbonImmutable $end, int $limit = 100): array
    {
        $models = LargeMammalGroomingSessionModel::where('tenant_id', $tenantId)
            ->whereBetween('started_at', [$start, $end])
            ->limit($limit)
            ->latest('started_at')
            ->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findIncompleteByGroomer(int $groomerId): ?LargeMammalGroomingSession
    {
        $model = LargeMammalGroomingSessionModel::where('groomer_id', $groomerId)
            ->where('status', 'in_progress')
            ->first();
        return $model?->toDomain();
    }

    public function save(LargeMammalGroomingSession $session): LargeMammalGroomingSession
    {
        $model = LargeMammalGroomingSessionModel::fromDomain($session);
        $model->save();
        return $model->toDomain();
    }

    public function delete(int $id): void
    {
        LargeMammalGroomingSessionModel::destroy($id);
    }
}
