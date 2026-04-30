<?php

declare(strict_types=1);

namespace Modules\Fitness\Infrastructure\Repositories\Senior;

use Modules\Fitness\Domain\Senior\Entities\SeniorProgram;
use Modules\Fitness\Domain\Senior\Repositories\SeniorProgramRepositoryInterface;
use Modules\Fitness\Infrastructure\Models\Senior\SeniorProgramModel;

final readonly class EloquentSeniorProgramRepository implements SeniorProgramRepositoryInterface
{
    public function save(SeniorProgram $program): SeniorProgram
    {
        $model = $program->id === 0
            ? SeniorProgramModel::fromDomain($program)
            : SeniorProgramModel::where('id', $program->id)->first();

        if ($model === null) {
            $model = SeniorProgramModel::fromDomain($program);
        } else {
            $model->updateFromDomain($program);
        }

        $model->save();

        return $model->toDomain();
    }

    public function findById(int $id): ?SeniorProgram
    {
        $model = SeniorProgramModel::find($id);

        return $model?->toDomain();
    }

    public function findByTenantId(int $tenantId): array
    {
        $models = SeniorProgramModel::where('tenant_id', $tenantId)->get();

        return $models->map(fn (SeniorProgramModel $model) => $model->toDomain())->toArray();
    }

    public function findActiveByTenantId(int $tenantId): array
    {
        $models = SeniorProgramModel::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get();

        return $models->map(fn (SeniorProgramModel $model) => $model->toDomain())->toArray();
    }

    public function findByFocusArea(int $tenantId, string $focusArea): array
    {
        $models = SeniorProgramModel::where('tenant_id', $tenantId)
            ->where('focus_area', $focusArea)
            ->get();

        return $models->map(fn (SeniorProgramModel $model) => $model->toDomain())->toArray();
    }

    public function delete(int $id): void
    {
        SeniorProgramModel::where('id', $id)->delete();
    }

    public function exists(int $id): bool
    {
        return SeniorProgramModel::where('id', $id)->exists();
    }
}
