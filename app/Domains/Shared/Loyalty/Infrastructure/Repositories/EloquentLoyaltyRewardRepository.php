<?php

declare(strict_types=1);

namespace Modules\Loyalty\Infrastructure\Repositories;

use Illuminate\Support\Facades\DB;
use Modules\Loyalty\Domain\Entities\LoyaltyReward;
use Modules\Loyalty\Domain\Repositories\LoyaltyRewardRepositoryInterface;
use Modules\Loyalty\Infrastructure\Models\LoyaltyRewardModel;

final readonly class EloquentLoyaltyRewardRepository implements LoyaltyRewardRepositoryInterface
{
    public function findById(string $id): ?LoyaltyReward
    {
        $model = LoyaltyRewardModel::find($id);
        return $model?->toDomain();
    }

    public function findByUuid(string $uuid): ?LoyaltyReward
    {
        $model = LoyaltyRewardModel::where('uuid', $uuid)->first();
        return $model?->toDomain();
    }

    public function findByProgramId(string $programId): array
    {
        $models = LoyaltyRewardModel::where('loyalty_program_id', $programId)->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findAvailableByProgramId(string $programId): array
    {
        $models = LoyaltyRewardModel::where('loyalty_program_id', $programId)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now());
            })
            ->where(function ($query) {
                $query->whereNull('stock_quantity')
                    ->whereColumn('redeemed_count', '<', 'stock_quantity');
            })
            ->where(function ($query) {
                $query->whereNull('max_redemptions_total')
                    ->whereColumn('redeemed_count', '<', 'max_redemptions_total');
            })
            ->get();

        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findByProgramIdSorted(string $programId): array
    {
        $models = LoyaltyRewardModel::where('loyalty_program_id', $programId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('created_at')
            ->get();

        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function save(LoyaltyReward $reward): LoyaltyReward
    {
        return DB::transaction(function () use ($reward) {
            $model = LoyaltyRewardModel::fromDomain($reward);
            $model->save();

            if ($reward->getId() === '0') {
                $model = LoyaltyRewardModel::find($model->id);
                return $model->toDomain();
            }

            return $model->fresh()->toDomain();
        });
    }

    public function delete(string $id): void
    {
        LoyaltyRewardModel::findOrFail($id)->delete();
    }
}
