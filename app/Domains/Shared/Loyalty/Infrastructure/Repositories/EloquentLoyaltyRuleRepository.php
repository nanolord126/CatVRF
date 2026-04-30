<?php

declare(strict_types=1);

namespace Modules\Loyalty\Infrastructure\Repositories;

use Illuminate\Support\Facades\DB;
use Modules\Loyalty\Domain\Entities\LoyaltyRule;
use Modules\Loyalty\Domain\Repositories\LoyaltyRuleRepositoryInterface;
use Modules\Loyalty\Infrastructure\Models\LoyaltyRuleModel;

final readonly class EloquentLoyaltyRuleRepository implements LoyaltyRuleRepositoryInterface
{
    public function findById(string $id): ?LoyaltyRule
    {
        $model = LoyaltyRuleModel::find($id);
        return $model?->toDomain();
    }

    public function findByUuid(string $uuid): ?LoyaltyRule
    {
        $model = LoyaltyRuleModel::where('uuid', $uuid)->first();
        return $model?->toDomain();
    }

    public function findByProgramId(string $programId): array
    {
        $models = LoyaltyRuleModel::where('loyalty_program_id', $programId)->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findActiveByProgramId(string $programId): array
    {
        $models = LoyaltyRuleModel::where('loyalty_program_id', $programId)
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
                $query->whereNull('max_uses_total')
                    ->whereColumn('current_uses', '<', 'max_uses_total');
            })
            ->get();

        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findByProgramIdSorted(string $programId): array
    {
        $models = LoyaltyRuleModel::where('loyalty_program_id', $programId)
            ->where('is_active', true)
            ->orderByDesc('priority')
            ->orderBy('created_at')
            ->get();

        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function save(LoyaltyRule $rule): LoyaltyRule
    {
        return DB::transaction(function () use ($rule) {
            $model = LoyaltyRuleModel::fromDomain($rule);
            $model->save();

            if ($rule->getId() === '0') {
                $model = LoyaltyRuleModel::find($model->id);
                return $model->toDomain();
            }

            return $model->fresh()->toDomain();
        });
    }

    public function delete(string $id): void
    {
        LoyaltyRuleModel::findOrFail($id)->delete();
    }
}
