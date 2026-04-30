<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Infrastructure\Repositories;

use Modules\VetGrooming\Domain\Entities\ExoticSafetyProtocol;
use Modules\VetGrooming\Domain\Repositories\ExoticSafetyProtocolRepositoryInterface;
use Modules\VetGrooming\Domain\Enums\ExoticCategory;
use Modules\VetGrooming\Domain\Enums\ExoticGroup;
use Modules\VetGrooming\Infrastructure\Models\ExoticSafetyProtocolModel;

final class ExoticSafetyProtocolRepository implements ExoticSafetyProtocolRepositoryInterface
{
    public function findById(int $id): ?ExoticSafetyProtocol
    {
        $model = ExoticSafetyProtocolModel::find($id);
        return $model?->toDomain();
    }

    public function findByTenant(int $tenantId, int $limit = 100): array
    {
        $models = ExoticSafetyProtocolModel::where('tenant_id', $tenantId)
            ->limit($limit)
            ->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findActiveByTenant(int $tenantId, int $limit = 100): array
    {
        $models = ExoticSafetyProtocolModel::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->limit($limit)
            ->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findByCategory(ExoticCategory $category, int $tenantId, int $limit = 100): array
    {
        $models = ExoticSafetyProtocolModel::where('tenant_id', $tenantId)
            ->where('category', $category->value)
            ->limit($limit)
            ->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findByGroup(ExoticGroup $group, int $tenantId, int $limit = 100): array
    {
        $models = ExoticSafetyProtocolModel::where('tenant_id', $tenantId)
            ->where('group', $group->value)
            ->limit($limit)
            ->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findBySpecies(string $species, int $tenantId, int $limit = 50): array
    {
        $models = ExoticSafetyProtocolModel::where('tenant_id', $tenantId)
            ->where('species', 'like', "%{$species}%")
            ->limit($limit)
            ->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findByCategoryAndSpecies(ExoticCategory $category, string $species, int $tenantId): ?ExoticSafetyProtocol
    {
        $model = ExoticSafetyProtocolModel::where('tenant_id', $tenantId)
            ->where('category', $category->value)
            ->where('species', $species)
            ->where('is_active', true)
            ->first();
        return $model?->toDomain();
    }

    public function findByGroupAndSpecies(ExoticGroup $group, string $species, int $tenantId): ?ExoticSafetyProtocol
    {
        $model = ExoticSafetyProtocolModel::where('tenant_id', $tenantId)
            ->where('group', $group->value)
            ->where('species', $species)
            ->where('is_active', true)
            ->first();
        return $model?->toDomain();
    }

    public function save(ExoticSafetyProtocol $protocol): ExoticSafetyProtocol
    {
        $model = ExoticSafetyProtocolModel::fromDomain($protocol);
        $model->save();
        return $model->toDomain();
    }

    public function delete(int $id): void
    {
        ExoticSafetyProtocolModel::destroy($id);
    }
}
