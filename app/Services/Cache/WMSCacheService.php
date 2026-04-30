<?php

declare(strict_types=1);

namespace App\Services\Cache;

use Illuminate\Support\Facades\Cache;
use Psr\Log\LoggerInterface;

/**
 * WMS Cache Service
 *
 * Provides caching layer for WMS queries to improve performance:
 * - Inventory item data caching
 * - Stock level caching with automatic invalidation
 * - Batch data caching
 * - Warehouse configuration caching
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class WMSCacheService
{
    private const DEFAULT_TTL = 3600; // 1 hour
    private const STOCK_TTL = 300; // 5 minutes
    private const CONFIG_TTL = 7200; // 2 hours

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Get inventory item with caching
     *
     * @param  int  $itemId  Item ID
     * @param  callable  $callback  Data fetcher
     * @return mixed
     */
    public function getInventoryItem(int $itemId, callable $callback): mixed
    {
        $key = "inventory_item:{$itemId}";

        return Cache::tags(['inventory', "item:{$itemId}"])->remember(
            $key,
            self::DEFAULT_TTL,
            $callback
        );
    }

    /**
     * Get stock level with caching
     *
     * @param  int  $itemId  Item ID
     * @param  int  $warehouseId  Warehouse ID
     * @param  callable  $callback  Data fetcher
     * @return mixed
     */
    public function getStockLevel(int $itemId, int $warehouseId, callable $callback): mixed
    {
        $key = "stock_level:{$itemId}:{$warehouseId}";

        return Cache::tags(['inventory', 'stock', "item:{$itemId}", "warehouse:{$warehouseId}"])->remember(
            $key,
            self::STOCK_TTL,
            $callback
        );
    }

    /**
     * Get batch data with caching
     *
     * @param  string  $batchId  Batch ID
     * @param  callable  $callback  Data fetcher
     * @return mixed
     */
    public function getBatch(string $batchId, callable $callback): mixed
    {
        $key = "batch:{$batchId}";

        return Cache::tags(['inventory', 'batch', "batch:{$batchId}"])->remember(
            $key,
            self::DEFAULT_TTL,
            $callback
        );
    }

    /**
     * Get warehouse configuration with caching
     *
     * @param  int  $warehouseId  Warehouse ID
     * @param  callable  $callback  Data fetcher
     * @return mixed
     */
    public function getWarehouseConfig(int $warehouseId, callable $callback): mixed
    {
        $key = "warehouse_config:{$warehouseId}";

        return Cache::tags(['warehouse', "warehouse:{$warehouseId}"])->remember(
            $key,
            self::CONFIG_TTL,
            $callback
        );
    }

    /**
     * Get FEFO picking order with caching
     *
     * @param  int  $productId  Product ID
     * @param  int  $quantity  Quantity
     * @param  callable  $callback  Data fetcher
     * @return mixed
     */
    public function getFEFOPickingOrder(int $productId, int $quantity, callable $callback): mixed
    {
        $key = "fefo_picking:{$productId}:{$quantity}";

        return Cache::tags(['inventory', 'fefo', "product:{$productId}"])->remember(
            $key,
            self::STOCK_TTL,
            $callback
        );
    }

    /**
     * Get reorder recommendations with caching
     *
     * @param  int  $tenantId  Tenant ID
     * @param  callable  $callback  Data fetcher
     * @return mixed
     */
    public function getReorderRecommendations(int $tenantId, callable $callback): mixed
    {
        $key = "reorder_recommendations:{$tenantId}";

        return Cache::tags(['inventory', 'reorder', "tenant:{$tenantId}"])->remember(
            $key,
            self::STOCK_TTL,
            $callback
        );
    }

    /**
     * Invalidate inventory item cache
     *
     * @param  int  $itemId  Item ID
     * @return void
     */
    public function invalidateInventoryItem(int $itemId): void
    {
        Cache::tags(['inventory', "item:{$itemId}"])->flush();

        $this->logger->debug('Inventory item cache invalidated', [
            'item_id' => $itemId,
        ]);
    }

    /**
     * Invalidate stock level cache
     *
     * @param  int  $itemId  Item ID
     * @param  int  $warehouseId  Warehouse ID
     * @return void
     */
    public function invalidateStockLevel(int $itemId, int $warehouseId): void
    {
        Cache::tags(['inventory', 'stock', "item:{$itemId}", "warehouse:{$warehouseId}"])->flush();

        $this->logger->debug('Stock level cache invalidated', [
            'item_id' => $itemId,
            'warehouse_id' => $warehouseId,
        ]);
    }

    /**
     * Invalidate batch cache
     *
     * @param  string  $batchId  Batch ID
     * @return void
     */
    public function invalidateBatch(string $batchId): void
    {
        Cache::tags(['inventory', 'batch', "batch:{$batchId}"])->flush();

        $this->logger->debug('Batch cache invalidated', [
            'batch_id' => $batchId,
        ]);
    }

    /**
     * Invalidate warehouse cache
     *
     * @param  int  $warehouseId  Warehouse ID
     * @return void
     */
    public function invalidateWarehouse(int $warehouseId): void
    {
        Cache::tags(['warehouse', "warehouse:{$warehouseId}"])->flush();

        $this->logger->debug('Warehouse cache invalidated', [
            'warehouse_id' => $warehouseId,
        ]);
    }

    /**
     * Invalidate all inventory cache for tenant
     *
     * @param  int  $tenantId  Tenant ID
     * @return void
     */
    public function invalidateTenantInventory(int $tenantId): void
    {
        Cache::tags(['inventory', "tenant:{$tenantId}"])->flush();

        $this->logger->debug('Tenant inventory cache invalidated', [
            'tenant_id' => $tenantId,
        ]);
    }

    /**
     * Invalidate FEFO picking cache for product
     *
     * @param  int  $productId  Product ID
     * @return void
     */
    public function invalidateFEFOPicking(int $productId): void
    {
        Cache::tags(['inventory', 'fefo', "product:{$productId}"])->flush();

        $this->logger->debug('FEFO picking cache invalidated', [
            'product_id' => $productId,
        ]);
    }

    /**
     * Warm up cache for frequently accessed items
     *
     * @param  int  $warehouseId  Warehouse ID
     * @param  array<int>  $itemIds  Item IDs to warm up
     * @param  callable  $callback  Data fetcher
     * @return void
     */
    public function warmUpCache(int $warehouseId, array $itemIds, callable $callback): void
    {
        foreach ($itemIds as $itemId) {
            $this->getStockLevel($itemId, $warehouseId, function () use ($callback, $itemId, $warehouseId) {
                return $callback($itemId, $warehouseId);
            });
        }

        $this->logger->info('Cache warmed up', [
            'warehouse_id' => $warehouseId,
            'items_count' => count($itemIds),
        ]);
    }

    /**
     * Clear all WMS cache
     *
     * @return void
     */
    public function clearAll(): void
    {
        Cache::tags(['inventory', 'warehouse'])->flush();

        $this->logger->info('All WMS cache cleared');
    }

    /**
     * Get cache statistics
     *
     * @return array
     */
    public function getStatistics(): array
    {
        return [
            'default_ttl' => self::DEFAULT_TTL,
            'stock_ttl' => self::STOCK_TTL,
            'config_ttl' => self::CONFIG_TTL,
            'tags_in_use' => ['inventory', 'warehouse', 'stock', 'batch', 'fefo', 'reorder'],
        ];
    }
}
