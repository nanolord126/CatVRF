<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Repositories;

use Modules\Inventory\Domain\Entities\InventoryItem;
use Illuminate\Support\Collection;

/**
 * Inventory Item Repository Interface
 *
 * Defines contract for inventory item data access following Dependency Inversion Principle.
 * Implementations should be in Infrastructure layer.
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
interface InventoryItemRepositoryInterface
{
    /**
     * Find an inventory item by ID
     *
     * @param  int  $id
     * @return InventoryItem|null
     */
    public function findById(int $id): ?InventoryItem;

    /**
     * Find an inventory item by SKU
     *
     * @param  string  $sku
     * @return InventoryItem|null
     */
    public function findBySku(string $sku): ?InventoryItem;

    /**
     * Find an inventory item by barcode
     *
     * @param  string  $barcode
     * @return InventoryItem|null
     */
    public function findByBarcode(string $barcode): ?InventoryItem;

    /**
     * Get all inventory items for a tenant
     *
     * @param  int  $tenantId
     * @return Collection<int, InventoryItem>
     */
    public function findByTenant(int $tenantId): Collection;

    /**
     * Get inventory items by category
     *
     * @param  int  $tenantId
     * @param  string  $category
     * @return Collection<int, InventoryItem>
     */
    public function findByCategory(int $tenantId, string $category): Collection;

    /**
     * Get items expiring soon
     *
     * @param  int  $tenantId
     * @param  int  $daysWithin
     * @return Collection<int, InventoryItem>
     */
    public function findExpiringSoon(int $tenantId, int $daysWithin = 30): Collection;

    /**
     * Get expired items
     *
     * @param  int  $tenantId
     * @return Collection<int, InventoryItem>
     */
    public function findExpired(int $tenantId): Collection;

    /**
     * Get items with low stock
     *
     * @param  int  $tenantId
     * @return Collection<int, InventoryItem>
     */
    public function findLowStock(int $tenantId): Collection;

    /**
     * Save (create or update) an inventory item
     *
     * @param  InventoryItem  $item
     * @return void
     */
    public function save(InventoryItem $item): void;

    /**
     * Delete an inventory item
     *
     * @param  int  $id
     * @return void
     */
    public function delete(int $id): void;
}
