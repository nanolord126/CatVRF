<?php

declare(strict_types=1);

namespace Modules\Loyalty\Infrastructure\Repositories;

use Illuminate\Support\Facades\DB;
use Modules\Loyalty\Domain\Entities\LoyaltyTier;
use Modules\Loyalty\Domain\Repositories\LoyaltyTierRepositoryInterface;
use Modules\Loyalty\Infrastructure\Models\LoyaltyTierModel;

final readonly class EloquentLoyaltyTierRepository implements LoyaltyTierRepositoryInterface
{
    public function findById(string $id): ?LoyaltyTier
    {
        $model = LoyaltyTierModel::find($id);
        return $model?->toDomain();
    }

    public function findByUuid(string $uuid): ?LoyaltyTier
    {
        $model = LoyaltyTierModel::where('uuid', $uuid)->first();
        return $model?->toDomain();
    }

    public function findByProgramId(string $programId): array
    {
        $models = LoyaltyTierModel::where('loyalty_program_id', $programId)->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findBySlug(string $slug): ?LoyaltyTier
    {
        $model = LoyaltyTierModel::where('slug', $slug)->first();
        return $model?->toDomain();
    }

    public function findByProgramIdSorted(string $programId): array
    {
        $models = LoyaltyTierModel::where('loyalty_program_id', $programId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('min_points')
            ->get();

        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function save(LoyaltyTier $tier): LoyaltyTier
    {
        return DB::transaction(function () use ($tier) {
            $model = LoyaltyTierModel::fromDomain($tier);
            $model->save();

            if ($tier->getId() === '0') {
                $model = LoyaltyTierModel::find($model->id);
                return $model->toDomain();
            }

            return $model->fresh()->toDomain();
        });
    }

    public function delete(string $id): void
    {
        LoyaltyTierModel::findOrFail($id)->delete();
    }
}
