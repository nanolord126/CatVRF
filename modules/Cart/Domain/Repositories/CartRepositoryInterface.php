<?php

declare(strict_types=1);

namespace Modules\Cart\Domain\Repositories;

use Modules\Cart\Domain\Entities\Cart;

interface CartRepositoryInterface
{
    public function create(array $data): Cart;

    public function update(int $id, array $data): Cart;

    public function findById(int $id): ?Cart;

    public function findByUuid(string $uuid): ?Cart;

    public function findByUserAndSeller(int $userId, int $sellerId): ?Cart;

    public function findActiveByUser(int $userId): array;

    public function countActiveByUser(int $userId): int;

    public function findExpired(): array;
}
