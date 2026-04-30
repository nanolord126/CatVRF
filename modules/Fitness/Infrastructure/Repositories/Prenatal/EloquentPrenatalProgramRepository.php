<?php

declare(strict_types=1);

namespace Modules\Fitness\Infrastructure\Repositories\Prenatal;

use Modules\Fitness\Domain\Prenatal\Entities\PrenatalProgram;
use Modules\Fitness\Domain\Prenatal\Repositories\PrenatalProgramRepositoryInterface;
use Modules\Fitness\Infrastructure\Models\Prenatal\PrenatalProgramModel;

final readonly class EloquentPrenatalProgramRepository implements PrenatalProgramRepositoryInterface
{
    public function save(PrenatalProgram $program): PrenatalProgram
    {
        $model = $program->id > 0
            ? PrenatalProgramModel::findOrFail($program->id)
            : new PrenatalProgramModel();

        if ($program->id > 0) {
            $model->updateFromDomain($program);
        } else {
            $model = PrenatalProgramModel::fromDomain($program);
        }

        $model->save();

        return $model->toDomain();
    }

    public function findById(int $id): ?PrenatalProgram
    {
        $model = PrenatalProgramModel::find($id);
        return $model?->toDomain();
    }

    public function findByTenantId(int $tenantId): array
    {
        $models = PrenatalProgramModel::where('tenant_id', $tenantId)->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findActiveByTenantId(int $tenantId): array
    {
        $models = PrenatalProgramModel::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findByTargetTrimester(int $tenantId, string $trimester): array
    {
        $models = PrenatalProgramModel::where('tenant_id', $tenantId)
            ->where('target_trimester', $trimester)
            ->where('is_active', true)
            ->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function delete(int $id): void
    {
        PrenatalProgramModel::findOrFail($id)->delete();
    }

    public function exists(int $id): bool
    {
        return PrenatalProgramModel::where('id', $id)->exists();
    }
}
