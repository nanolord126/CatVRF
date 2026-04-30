<?php

declare(strict_types=1);

namespace Modules\Supermarket\Domain\Repositories;

use Modules\Supermarket\Application\DTOs\CreateReturnData;
use Modules\Supermarket\Infrastructure\Models\Return;
use Illuminate\Pagination\LengthAwarePaginator;

interface ReturnRepositoryInterface
{
    public function findById(int $id): ?Return;

    public function findByOrderId(int $orderId): ?Return;

    public function findByBuyerId(int $buyerId, int $perPage = 15): LengthAwarePaginator;

    public function findBySellerId(int $sellerId, int $perPage = 15): LengthAwarePaginator;

    public function findPending(): \Illuminate\Database\Eloquent\Collection;

    public function create(CreateReturnData $data): Return;

    public function update(Return $return, array $data): bool;

    public function delete(Return $return): bool;

    public function getStatsBySeller(int $sellerId, string $period = '30d'): array;
}
