<?php

declare(strict_types=1);

namespace Modules\Flowers\Domain\Repositories;

use Modules\Flowers\Domain\Entities\Order;
use Modules\Flowers\Domain\Enums\OrderStatus;
use Illuminate\Pagination\LengthAwarePaginator;

interface OrderRepositoryInterface
{
    public function findById(int $id): ?Order;

    public function findByOrderNumber(string $orderNumber): ?Order;

    public function save(Order $order): Order;

    public function delete(int $id): void;

    public function getByVenue(int $venueId, array $filters = []): LengthAwarePaginator;

    public function getByClient(int $clientId, array $filters = []): LengthAwarePaginator;

    public function getByFlorist(int $floristId, array $filters = []): LengthAwarePaginator;

    public function getByStatus(OrderStatus $status, int $venueId = null): array;

    public function getPendingOrders(int $venueId): array;

    public function getInAssemblyOrders(int $venueId): array;

    public function getReadyForDeliveryOrders(int $venueId): array;

    public function getOverdueOrders(int $venueId): array;

    public function getUrgentOrders(int $venueId): array;

    public function getCorporateOrders(int $venueId): array;
}
