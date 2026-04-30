<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Infrastructure\Repositories;

use Modules\VetGrooming\Domain\Entities\ExoticGroomingSession;
use Modules\VetGrooming\Domain\Repositories\ExoticGroomingSessionRepositoryInterface;
use Modules\VetGrooming\Domain\Enums\ExoticCategory;
use Carbon\CarbonImmutable;

final class ExoticGroomingSessionRepository implements ExoticGroomingSessionRepositoryInterface
{
    public function findById(int $id): ?ExoticGroomingSession
    {
        $model = \Modules\VetGrooming\Infrastructure\Models\ExoticGroomingSessionModel::find($id);
        return $model?->toDomain();
    }

    public function findByPetId(int $petId): array
    {
        $models = \Modules\VetGrooming\Infrastructure\Models\ExoticGroomingSessionModel::where('pet_id', $petId)->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findByGroomerId(int $groomerId, int $limit = 100): array
    {
        $models = \Modules\VetGrooming\Infrastructure\Models\ExoticGroomingSessionModel::where('groomer_id', $groomerId)
            ->limit($limit)
            ->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findByTenant(int $tenantId, int $limit = 100): array
    {
        $models = \Modules\VetGrooming\Infrastructure\Models\ExoticGroomingSessionModel::where('tenant_id', $tenantId)
            ->limit($limit)
            ->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findByCategory(ExoticCategory $category, int $tenantId, int $limit = 100): array
    {
        $models = \Modules\VetGrooming\Infrastructure\Models\ExoticGroomingSessionModel::where('tenant_id', $tenantId)
            ->where('exotic_type', $category->value)
            ->limit($limit)
            ->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findBySpeciesGroup(string $speciesGroup, int $tenantId, int $limit = 100): array
    {
        $models = \Modules\VetGrooming\Infrastructure\Models\ExoticGroomingSessionModel::where('tenant_id', $tenantId)
            ->where('species_group', $speciesGroup)
            ->limit($limit)
            ->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findHighStressSessions(int $tenantId, int $limit = 50): array
    {
        $models = \Modules\VetGrooming\Infrastructure\Models\ExoticGroomingSessionModel::where('tenant_id', $tenantId)
            ->where('stress_level_after', '>=', 7)
            ->limit($limit)
            ->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findWithSedation(int $tenantId, int $limit = 50): array
    {
        $models = \Modules\VetGrooming\Infrastructure\Models\ExoticGroomingSessionModel::where('tenant_id', $tenantId)
            ->where('sedation_used', true)
            ->limit($limit)
            ->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findByDateRange(int $tenantId, CarbonImmutable $start, CarbonImmutable $end, int $limit = 100): array
    {
        $models = \Modules\VetGrooming\Infrastructure\Models\ExoticGroomingSessionModel::where('tenant_id', $tenantId)
            ->whereBetween('started_at', [$start, $end])
            ->limit($limit)
            ->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findIncompleteByGroomer(int $groomerId): ?ExoticGroomingSession
    {
        $model = \Modules\VetGrooming\Infrastructure\Models\ExoticGroomingSessionModel::where('groomer_id', $groomerId)
            ->where('status', 'in_progress')
            ->first();
        return $model?->toDomain();
    }

    public function save(ExoticGroomingSession $session): ExoticGroomingSession
    {
        $model = \Modules\VetGrooming\Infrastructure\Models\ExoticGroomingSessionModel::fromDomain($session);
        $model->save();
        return $model->toDomain();
    }

    public function delete(int $id): void
    {
        \Modules\VetGrooming\Infrastructure\Models\ExoticGroomingSessionModel::destroy($id);
    }
}
