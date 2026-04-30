<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Repositories;

use Modules\CatCRM\Domain\Verticals\Supermarket\SupermarketOrder;
use Illuminate\Support\Collection;

/**
 * Interface for Supermarket Order Repository
 */
interface SupermarketOrderRepositoryInterface
{
    /**
     * Find order by ID
     */
    public function findById(int $id): ?SupermarketOrder;

    /**
     * Find order by UUID
     */
    public function findByUuid(string $uuid): ?SupermarketOrder;

    /**
     * Find order by order number
     */
    public function findByOrderNumber(string $orderNumber): ?SupermarketOrder;

    /**
     * Find orders by deal ID
     */
    public function findByDealId(int $dealId): Collection;

    /**
     * Find orders by customer ID
     */
    public function findByCustomerId(int $customerId, int $limit = 50): Collection;

    /**
     * Find orders by tenant ID
     */
    public function findByTenantId(int $tenantId, ?int $businessGroupId = null, int $limit = 100): Collection;

    /**
     * Find orders by type
     */
    public function findByType(string $type, int $tenantId, ?int $businessGroupId = null): Collection;

    /**
     * Find orders by status
     */
    public function findByStatus(string $status, int $tenantId, ?int $businessGroupId = null): Collection;

    /**
     * Find subscription orders
     */
    public function findSubscriptions(int $tenantId, ?int $businessGroupId = null): Collection;

    /**
     * Find return orders
     */
    public function findReturns(int $tenantId, ?int $businessGroupId = null): Collection;

    /**
     * Find orders requiring age verification
     */
    public function findRequiringAgeVerification(int $tenantId, ?int $businessGroupId = null): Collection;

    /**
     * Find orders with honesty marks
     */
    public function findWithHonestyMarks(int $tenantId, ?int $businessGroupId = null): Collection;

    /**
     * Find upcoming deliveries
     */
    public function findUpcomingDeliveries(int $tenantId, ?int $businessGroupId = null, int $hours = 24): Collection;

    /**
     * Find today's orders
     */
    public function findTodayOrders(int $tenantId, ?int $businessGroupId = null): Collection;

    /**
     * Create new order
     */
    public function create(array $data): SupermarketOrder;

    /**
     * Update order
     */
    public function update(SupermarketOrder $order, array $data): bool;

    /**
     * Delete order (soft delete)
     */
    public function delete(SupermarketOrder $order): bool;

    /**
     * Get statistics by tenant
     */
    public function getStatistics(int $tenantId, ?int $businessGroupId = null): array;
}
