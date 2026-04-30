<?php

declare(strict_types=1);

namespace Modules\Cart\Domain\Repositories;

use Modules\Cart\Domain\Entities\CartItem;

interface CartItemRepositoryInterface
{
    public function create(array $data): CartItem;

    public function update(int $id, array $data): CartItem;

    public function findById(int $id): ?CartItem;

    public function findByCartId(int $cartId): array;

    public function findByCartAndProduct(int $cartId, int $productId): ?CartItem;

    public function deleteByCartAndProduct(int $cartId, int $productId): void;

    public function deleteByCartId(int $cartId): void;

    public function getTotal(int $cartId): int;
}
