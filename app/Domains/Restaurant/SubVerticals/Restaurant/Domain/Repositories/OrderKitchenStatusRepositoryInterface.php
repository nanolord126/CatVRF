<?php

declare(strict_types=1);

namespace Modules\Restaurant\Domain\Repositories;

use Modules\Restaurant\Domain\Entities\OrderKitchenStatus;
use Modules\Restaurant\Domain\Enums\OrderKitchenStatusEnum;
use Modules\Restaurant\Domain\Enums\KitchenStationType;
use Illuminate\Support\Collection;

interface OrderKitchenStatusRepositoryInterface
{
    public function findById(int $id): ?OrderKitchenStatus;

    public function findByOrderId(int $orderId): Collection;

    public function findByStationId(int $stationId): Collection;

    public function findByStatus(OrderKitchenStatusEnum $status): Collection;

    public function findActiveOrdersForStation(int $stationId): Collection;

    public function save(OrderKitchenStatus $status): void;

    public function delete(int $id): void;

    public function getOverdueOrders(): Collection;

    public function getOrdersByPriority(int $tenantId): Collection;
}
