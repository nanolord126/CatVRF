<?php

declare(strict_types=1);

namespace Modules\Cart\Infrastructure\Repositories;

use Carbon\CarbonImmutable;
use Modules\Cart\Domain\Entities\Cart;
use Modules\Cart\Domain\Repositories\CartRepositoryInterface;
use Modules\Cart\Infrastructure\Models\CartModel;

final class EloquentCartRepository implements CartRepositoryInterface
{
    public function create(array $data): Cart
    {
        $model = CartModel::create($data);
        return $model->toDomain();
    }

    public function update(int $id, array $data): Cart
    {
        $model = CartModel::findOrFail($id);
        $model->update($data);
        return $model->fresh()->toDomain();
    }

    public function findById(int $id): ?Cart
    {
        $model = CartModel::find($id);
        return $model?->toDomain();
    }

    public function findByUuid(string $uuid): ?Cart
    {
        $model = CartModel::where('uuid', $uuid)->first();
        return $model?->toDomain();
    }

    public function findByUserAndSeller(int $userId, int $sellerId): ?Cart
    {
        $model = CartModel::where('user_id', $userId)
            ->where('seller_id', $sellerId)
            ->active()
            ->first();
        return $model?->toDomain();
    }

    public function findActiveByUser(int $userId): array
    {
        return CartModel::where('user_id', $userId)
            ->active()
            ->with('items')
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(fn ($model) => $model->toDomain())
            ->toArray();
    }

    public function countActiveByUser(int $userId): int
    {
        return CartModel::where('user_id', $userId)
            ->active()
            ->count();
    }

    public function findExpired(): array
    {
        return CartModel::whereNotNull('reserved_until')
            ->where('reserved_until', '<', CarbonImmutable::now())
            ->get()
            ->map(fn ($model) => $model->toDomain())
            ->toArray();
    }
}
