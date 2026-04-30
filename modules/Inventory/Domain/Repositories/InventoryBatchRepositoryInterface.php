<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Repositories;

use Modules\Inventory\Domain\Entities\InventoryBatch;
use Illuminate\Support\Collection;

/**
 * Inventory Batch Repository Interface
 *
 * Defines contract for inventory batch data access following Dependency Inversion Principle.
 * Implementations should be in Infrastructure layer.
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
interface InventoryBatchRepositoryInterface
{
    /**
     * Find a batch by ID
     *
     * @param  int  $id
     * @return InventoryBatch|null
     */
    public function findById(int $id): ?InventoryBatch;

    /**
     * Find a batch by batch number within an item
     *
     * @param  int  $inventoryItemId
     * @param  string  $batchNumber
     * @return InventoryBatch|null
     */
    public function findByBatchNumber(int $inventoryItemId, string $batchNumber): ?InventoryBatch;

    /**
     * Get all batches for an inventory item
     *
     * @param  int  $inventoryItemId
     * @return Collection<int, InventoryBatch>
     */
    public function findByInventoryItem(int $inventoryItemId): Collection;

    /**
     * Get batches for a tenant
     *
     * @param  int  $tenantId
     * @return Collection<int, InventoryBatch>
     */
    public function findByTenant(int $tenantId): Collection;

    /**
     * Get the next expiring batch for an item
     *
     * @param  int  $inventoryItemId
     * @return InventoryBatch|null
     */
    public function getNextExpiringBatch(int $inventoryItemId): ?InventoryBatch;

    /**
     * Get usable batches (not expired, not quarantine) for an item
     *
     * @param  int  $inventoryItemId
     * @return Collection<int, InventoryBatch>
     */
    public function getUsableBatches(int $inventoryItemId): Collection;

    /**
     * Get batches expiring soon
     *
     * @param  int  $tenantId
     * @param  int  $daysWithin
     * @return Collection<int, InventoryBatch>
     */
    public function findExpiringSoon(int $tenantId, int $daysWithin = 30): Collection;

    /**
     * Get expired batches
     *
     * @param  int  $tenantId
     * @return Collection<int, InventoryBatch>
     */
    public function findExpired(int $tenantId): Collection;

    /**
     * Save (create or update) a batch
     *
     * @param  InventoryBatch  $batch
     * @return void
     */
    public function save(InventoryBatch $batch): void;

    /**
     * Delete a batch
     *
     * @param  int  $id
     * @return void
     */
    public function delete(int $id): void;

    /**
     * Update batch quantity
     *
     * @param  int  $batchId
     * @param  int  $newQuantity
     * @return void
     */
    public function updateQuantity(int $batchId, int $newQuantity): void;

    /**
     * Update batch status
     *
     * @param  int  $batchId
     * @param  string  $status
     * @return void
     */
    public function updateStatus(int $batchId, string $status): void;
}
