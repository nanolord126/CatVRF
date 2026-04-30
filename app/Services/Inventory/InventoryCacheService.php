<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;
use Illuminate\Database\DatabaseManager;
use Illuminate\Cache\Repository as CacheRepository;
use Psr\Log\LoggerInterface;

/**
 * Inventory Cache Service
 *
 * Caching layer for frequently requested inventory data:
 * - Product information
 * - Inventory levels
 * - Warehouse locations
 * - Batch information
 * - TTL-based invalidation
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class InventoryCacheService
{
    use WithAuditLogging;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService,
        private readonly CacheRepository $cache
    ) {}

    /**
     * Get product with caching
     *
     * @param  int  $productId  Product ID
     * @return array|null Product data
     */
    public function getProduct(int $productId): ?array
    {
        $cacheKey = "product:{$productId}";

        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $product = $this->db->table('products')
            ->where('id', $productId)
            ->first();

        if (! $product) {
            return null;
        }

        $productData = (array) $product;
        $this->cache->put($cacheKey, $productData, now()->addHours(1));

        return $productData;
    }

    /**
     * Get inventory item with caching
     *
     * @param  int  $inventoryItemId  Inventory item ID
     * @return array|null Inventory item data
     */
    public function getInventoryItem(int $inventoryItemId): ?array
    {
        $cacheKey = "inventory_item:{$inventoryItemId}";

        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $item = $this->db->table('inventory_items')
            ->where('id', $inventoryItemId)
            ->first();

        if (! $item) {
            return null;
        }

        $itemData = (array) $item;
        $this->cache->put($cacheKey, $itemData, now()->addMinutes(30));

        return $itemData;
    }

    /**
     * Get warehouse inventory with caching
     *
     * @param  int  $warehouseId  Warehouse ID
     * @return array Inventory data
     */
    public function getWarehouseInventory(int $warehouseId): array
    {
        $cacheKey = "warehouse_inventory:{$warehouseId}";

        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $items = $this->db->table('inventory_items')
            ->where('warehouse_id', $warehouseId)
            ->get()
            ->toArray();

        $this->cache->put($cacheKey, $items, now()->addMinutes(15));

        return $items;
    }

    /**
     * Get batch with caching
     *
     * @param  string  $batchId  Batch ID
     * @return array|null Batch data
     */
    public function getBatch(string $batchId): ?array
    {
        $cacheKey = "batch:{$batchId}";

        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $batch = $this->db->table('inventory_batches')
            ->where('id', $batchId)
            ->first();

        if (! $batch) {
            return null;
        }

        $batchData = (array) $batch;
        $this->cache->put($cacheKey, $batchData, now()->addHours(2));

        return $batchData;
    }

    /**
     * Invalidate product cache
     *
     * @param  int  $productId  Product ID
     * @return void
     */
    public function invalidateProduct(int $productId): void
    {
        $this->cache->forget("product:{$productId}");
    }

    /**
     * Invalidate inventory item cache
     *
     * @param  int  $inventoryItemId  Inventory item ID
     * @return void
     */
    public function invalidateInventoryItem(int $inventoryItemId): void
    {
        $this->cache->forget("inventory_item:{$inventoryItemId}");
    }

    /**
     * Invalidate warehouse inventory cache
     *
     * @param  int  $warehouseId  Warehouse ID
     * @return void
     */
    public function invalidateWarehouseInventory(int $warehouseId): void
    {
        $this->cache->forget("warehouse_inventory:{$warehouseId}");
        $this->cache->tags(["warehouse:{$warehouseId}"])->flush();
    }

    /**
     * Invalidate batch cache
     *
     * @param  string  $batchId  Batch ID
     * @return void
     */
    public function invalidateBatch(string $batchId): void
    {
        $this->cache->forget("batch:{$batchId}");
    }

    /**
     * Bulk cache warming for warehouse
     *
     * @param  int  $warehouseId  Warehouse ID
     * @return array Warm results
     */
    public function warmWarehouseCache(int $warehouseId): array
    {
        $results = [
            'warehouse_id' => $warehouseId,
            'products_cached' => 0,
            'items_cached' => 0,
            'batches_cached' => 0,
        ];

        $items = $this->db->table('inventory_items')
            ->where('warehouse_id', $warehouseId)
            ->select('id', 'product_id')
            ->get();

        foreach ($items as $item) {
            $this->getInventoryItem($item->id);
            $this->getProduct($item->product_id);
            $results['items_cached']++;
            $results['products_cached']++;
        }

        $batches = $this->db->table('inventory_batches')
            ->where('warehouse_id', $warehouseId)
            ->select('id')
            ->get();

        foreach ($batches as $batch) {
            $this->getBatch($batch->id);
            $results['batches_cached']++;
        }

        $this->logAction(
            action: 'warehouse_cache_warmed',
            entityType: 'Warehouse',
            entityId: $warehouseId,
            context: $results,
            userId: 0,
            tenantId: 0
        );

        return $results;
    }

    /**
     * Clear all inventory cache
     *
     * @return void
     */
    public function clearAllCache(): void
    {
        $this->cache->tags(['inventory'])->flush();
        $this->cache->tags(['warehouse'])->flush();
        $this->cache->tags(['product'])->flush();
    }

    /**
     * Get cache statistics
     *
     * @return array Cache stats
     */
    public function getCacheStats(): array
    {
        return [
            'cache_enabled' => config('cache.default'),
            'inventory_tag_enabled' => $this->cache->get('inventory:stats:enabled') ?? false,
            'warehouse_tag_enabled' => $this->cache->get('warehouse:stats:enabled') ?? false,
        ];
    }
}
