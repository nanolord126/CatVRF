<?php

declare(strict_types=1);

namespace Modules\Fitness\Infrastructure\Repositories\Kids;

use Modules\Fitness\Domain\Kids\Entities\KidsProgram;
use Modules\Fitness\Domain\Kids\Repositories\KidsProgramRepositoryInterface;
use Modules\Fitness\Infrastructure\Models\Kids\KidsProgramModel;

final readonly class EloquentKidsProgramRepository implements KidsProgramRepositoryInterface
{
    public function save(KidsProgram $program): KidsProgram
    {
        $model = $program->id > 0
            ? KidsProgramModel::findOrFail($program->id)
            : new KidsProgramModel();

        if ($program->id > 0) {
            $model->updateFromDomain($program);
        } else {
            $model = KidsProgramModel::fromDomain($program);
        }

        $model->save();

        return $model->toDomain();
    }

    public function findById(int $id): ?KidsProgram
    {
        $model = KidsProgramModel::find($id);
        return $model?->toDomain();
    }

    public function findByTenantId(int $tenantId): array
    {
        $models = KidsProgramModel::where('tenant_id', $tenantId)->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findActiveByTenantId(int $tenantId): array
    {
        $models = KidsProgramModel::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findByTargetAgeGroup(int $tenantId, string $ageGroup): array
    {
        $models = KidsProgramModel::where('tenant_id', $tenantId)
            ->where('target_age_group', $ageGroup)
            ->where('is_active', true)
            ->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function delete(int $id): void
    {
        KidsProgramModel::findOrFail($id)->delete();
    }

    public function exists(int $id): bool
    {
        return KidsProgramModel::where('id', $id)->exists();
    }
}
