<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\InventoryItem;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Inventory Item Repository Interface
 *
 * Contract for inventory item data access operations.
 * Follows Repository pattern for Clean Architecture.
 */
interface InventoryItemRepositoryInterface
{
    /**
     * Find inventory item by ID
     */
    public function findById(int $id): ?InventoryItem;

    /**
     * Find inventory item by SKU
     */
    public function findBySku(string $sku): ?InventoryItem;

    /**
     * Get all inventory items for a tenant
     */
    public function getAllForTenant(int $tenantId, array $filters = []): LengthAwarePaginator;

    /**
     * Get low stock items
     */
    public function getLowStockItems(int $tenantId, int $threshold = 10): array;

    /**
     * Get items expiring soon
     */
    public function getExpiringSoonItems(int $tenantId, int $days = 30): array;

    /**
     * Create new inventory item
     */
    public function create(array $data): InventoryItem;

    /**
     * Update inventory item
     */
    public function update(int $id, array $data, int $expectedVersion): bool;

    /**
     * Update stock quantity
     */
    public function updateStock(int $id, int $quantity, int $expectedVersion): bool;

    /**
     * Delete inventory item (soft delete)
     */
    public function delete(int $id): bool;

    /**
     * Reserve stock for an order
     */
    public function reserveStock(int $itemId, int $quantity): bool;

    /**
     * Release reserved stock
     */
    public function releaseReservedStock(int $itemId, int $quantity): bool;
}
