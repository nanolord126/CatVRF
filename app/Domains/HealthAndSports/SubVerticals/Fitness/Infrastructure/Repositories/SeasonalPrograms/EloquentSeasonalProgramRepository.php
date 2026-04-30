<?php

declare(strict_types=1);

namespace Modules\Fitness\Infrastructure\Repositories\SeasonalPrograms;

use Modules\Fitness\Domain\SeasonalPrograms\Entities\SeasonalProgram;
use Modules\Fitness\Domain\SeasonalPrograms\Repositories\SeasonalProgramRepositoryInterface;
use Modules\Fitness\Infrastructure\Models\SeasonalPrograms\SeasonalProgramModel;
use Illuminate\Database\Eloquent\Collection;

final readonly class EloquentSeasonalProgramRepository implements SeasonalProgramRepositoryInterface
{
    public function save(SeasonalProgram $program): SeasonalProgram
    {
        $model = $program->id === 0
            ? SeasonalProgramModel::fromDomain($program)
            : SeasonalProgramModel::where('id', $program->id)->first();

        if ($model === null) {
            $model = SeasonalProgramModel::fromDomain($program);
        } else {
            $model->updateFromDomain($program);
        }

        $model->save();

        return $model->toDomain();
    }

    public function findById(int $id): ?SeasonalProgram
    {
        $model = SeasonalProgramModel::find($id);

        return $model?->toDomain();
    }

    public function findByTenantId(int $tenantId): array
    {
        $models = SeasonalProgramModel::where('tenant_id', $tenantId)->get();

        return $models->map(fn (SeasonalProgramModel $model) => $model->toDomain())->toArray();
    }

    public function findByTenantIdAndStatus(int $tenantId, string $status): array
    {
        $models = SeasonalProgramModel::where('tenant_id', $tenantId)
            ->where('status', $status)
            ->get();

        return $models->map(fn (SeasonalProgramModel $model) => $model->toDomain())->toArray();
    }

    public function findActiveByTenantId(int $tenantId): array
    {
        $models = SeasonalProgramModel::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->where('period_start', '<=', now())
            ->where('period_end', '>=', now())
            ->get();

        return $models->map(fn (SeasonalProgramModel $model) => $model->toDomain())->toArray();
    }

    public function findUpcomingByTenantId(int $tenantId): array
    {
        $models = SeasonalProgramModel::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->where('period_start', '>', now())
            ->orderBy('period_start')
            ->get();

        return $models->map(fn (SeasonalProgramModel $model) => $model->toDomain())->toArray();
    }

    public function findOngoingByTenantId(int $tenantId): array
    {
        $models = SeasonalProgramModel::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->where('period_start', '<=', now())
            ->where('period_end', '>=', now())
            ->get();

        return $models->map(fn (SeasonalProgramModel $model) => $model->toDomain())->toArray();
    }

    public function findByType(int $tenantId, string $type): array
    {
        $models = SeasonalProgramModel::where('tenant_id', $tenantId)
            ->where('type', $type)
            ->get();

        return $models->map(fn (SeasonalProgramModel $model) => $model->toDomain())->toArray();
    }

    public function delete(int $id): void
    {
        SeasonalProgramModel::where('id', $id)->delete();
    }

    public function exists(int $id): bool
    {
        return SeasonalProgramModel::where('id', $id)->exists();
    }
}
