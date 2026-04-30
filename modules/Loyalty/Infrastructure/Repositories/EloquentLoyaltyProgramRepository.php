<?php

declare(strict_types=1);

namespace Modules\Loyalty\Infrastructure\Repositories;

use Illuminate\Support\Facades\DB;
use Modules\Loyalty\Domain\Entities\LoyaltyProgram;
use Modules\Loyalty\Domain\Repositories\LoyaltyProgramRepositoryInterface;
use Modules\Loyalty\Domain\Enums\VerticalType;
use Modules\Loyalty\Infrastructure\Models\LoyaltyProgramModel;

final readonly class EloquentLoyaltyProgramRepository implements LoyaltyProgramRepositoryInterface
{
    public function findById(string $id): ?LoyaltyProgram
    {
        $model = LoyaltyProgramModel::find($id);
        return $model?->toDomain();
    }

    public function findByUuid(string $uuid): ?LoyaltyProgram
    {
        $model = LoyaltyProgramModel::where('uuid', $uuid)->first();
        return $model?->toDomain();
    }

    public function findByTenantAndVertical(int $tenantId, VerticalType $verticalType): array
    {
        $models = LoyaltyProgramModel::where('tenant_id', $tenantId)
            ->where('vertical_type', $verticalType->value)
            ->get();

        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findActiveByTenant(int $tenantId): array
    {
        $models = LoyaltyProgramModel::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now());
            })
            ->get();

        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function save(LoyaltyProgram $program): LoyaltyProgram
    {
        return DB::transaction(function () use ($program) {
            $model = LoyaltyProgramModel::fromDomain($program);
            $model->save();

            if ($program->getId() === '0') {
                $model = LoyaltyProgramModel::find($model->id);
                return $model->toDomain();
            }

            return $model->fresh()->toDomain();
        });
    }

    public function delete(string $id): void
    {
        LoyaltyProgramModel::findOrFail($id)->delete();
    }
}
