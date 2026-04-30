<?php

declare(strict_types=1);

namespace Modules\Loyalty\Infrastructure\Repositories;

use Illuminate\Support\Facades\DB;
use Modules\Loyalty\Domain\Entities\LoyaltyTransaction;
use Modules\Loyalty\Domain\Repositories\LoyaltyTransactionRepositoryInterface;
use Modules\Loyalty\Infrastructure\Models\LoyaltyTransactionModel;

final readonly class EloquentLoyaltyTransactionRepository implements LoyaltyTransactionRepositoryInterface
{
    public function findById(string $id): ?LoyaltyTransaction
    {
        $model = LoyaltyTransactionModel::find($id);
        return $model?->toDomain();
    }

    public function findByUuid(string $uuid): ?LoyaltyTransaction
    {
        $model = LoyaltyTransactionModel::where('uuid', $uuid)->first();
        return $model?->toDomain();
    }

    public function findByProfileId(string $profileId, int $limit = 50): array
    {
        $models = LoyaltyTransactionModel::where('guest_loyalty_profile_id', $profileId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findBySource(string $sourceType, int $sourceId): ?LoyaltyTransaction
    {
        $model = LoyaltyTransactionModel::where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->first();

        return $model?->toDomain();
    }

    public function save(LoyaltyTransaction $transaction): LoyaltyTransaction
    {
        return DB::transaction(function () use ($transaction) {
            $model = LoyaltyTransactionModel::fromDomain($transaction);
            $model->save();

            if ($transaction->getId() === '0') {
                $model = LoyaltyTransactionModel::find($model->id);
                return $model->toDomain();
            }

            return $model->fresh()->toDomain();
        });
    }

    public function delete(string $id): void
    {
        LoyaltyTransactionModel::findOrFail($id)->delete();
    }
}
