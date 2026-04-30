<?php

declare(strict_types=1);

namespace Modules\Cart\Infrastructure\Repositories;

use Modules\Cart\Domain\Entities\CartItem;
use Modules\Cart\Domain\Repositories\CartItemRepositoryInterface;
use Modules\Cart\Infrastructure\Models\CartItemModel;

final class EloquentCartItemRepository implements CartItemRepositoryInterface
{
    public function create(array $data): CartItem
    {
        $model = CartItemModel::create($data);
        return $model->toDomain();
    }

    public function update(int $id, array $data): CartItem
    {
        $model = CartItemModel::findOrFail($id);
        $model->update($data);
        return $model->fresh()->toDomain();
    }

    public function findById(int $id): ?CartItem
    {
        $model = CartItemModel::find($id);
        return $model?->toDomain();
    }

    public function findByCartId(int $cartId): array
    {
        return CartItemModel::where('cart_id', $cartId)
            ->get()
            ->map(fn ($model) => $model->toDomain())
            ->toArray();
    }

    public function findByCartAndProduct(int $cartId, int $productId): ?CartItem
    {
        $model = CartItemModel::where('cart_id', $cartId)
            ->where('product_id', $productId)
            ->first();
        return $model?->toDomain();
    }

    public function deleteByCartAndProduct(int $cartId, int $productId): void
    {
        CartItemModel::where('cart_id', $cartId)
            ->where('product_id', $productId)
            ->delete();
    }

    public function deleteByCartId(int $cartId): void
    {
        CartItemModel::where('cart_id', $cartId)->delete();
    }

    public function getTotal(int $cartId): int
    {
        $items = $this->findByCartId($cartId);
        return array_reduce($items, fn ($sum, $item) => $sum + $item->getTotal(), 0);
    }
}
