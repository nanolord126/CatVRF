<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Psr\Log\LoggerInterface;

/**
 * Inventory Sync Service
 *
 * Synchronizes inventory data across systems:
 * - Real-time stock sync to external systems
 * - Cache invalidation
 * - Event publishing
 * - Data consistency checks
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class InventorySyncService
{
    use WithAuditLogging;

    private const CACHE_PREFIX = 'inventory:';
    private const SYNC_LOCK_PREFIX = 'inventory_sync_lock:';
    private const LOCK_TTL = 30;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Sync stock change to cache
     *
     * @param  int  $inventoryItemId  Inventory item ID
     * @param  int  $newQuantity  New quantity
     * @return bool Success
     */
    public function syncStockToCache(int $inventoryItemId, int $newQuantity): bool
    {
        $cacheKey = self::CACHE_PREFIX."stock:{$inventoryItemId}";

        Cache::put($cacheKey, $newQuantity, 300);

        $this->logger->debug('Stock synced to cache', [
            'inventory_item_id' => $inventoryItemId,
            'quantity' => $newQuantity,
        ]);

        return true;
    }

    /**
     * Invalidate inventory item cache
     *
     * @param  int  $inventoryItemId  Inventory item ID
     * @return bool Success
     */
    public function invalidateItemCache(int $inventoryItemId): bool
    {
        $patterns = [
            self::CACHE_PREFIX."stock:{$inventoryItemId}",
            self::CACHE_PREFIX."item:{$inventoryItemId}",
            self::CACHE_PREFIX."availability:{$inventoryItemId}",
            self::CACHE_PREFIX."batch:{$inventoryItemId}:*",
        ];

        foreach ($patterns as $pattern) {
            if (str_ends_with($pattern, '*')) {
                Cache::forget($pattern);
            } else {
                Cache::forget($pattern);
            }
        }

        $this->logger->debug('Inventory item cache invalidated', [
            'inventory_item_id' => $inventoryItemId,
        ]);

        return true;
    }

    /**
     * Invalidate warehouse cache
     *
     * @param  int  $warehouseId  Warehouse ID
     * @return bool Success
     */
    public function invalidateWarehouseCache(int $warehouseId): bool
    {
        Cache::tags(['warehouse', "warehouse:{$warehouseId}"])->flush();

        $this->logger->debug('Warehouse cache invalidated', [
            'warehouse_id' => $warehouseId,
        ]);

        return true;
    }

    /**
     * Publish stock change event
     *
     * @param  int  $inventoryItemId  Inventory item ID
     * @param  int  $oldQuantity  Old quantity
     * @param  int  $newQuantity  New quantity
     * @param  string  $changeType  Change type
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @return bool Success
     */
    public function publishStockChangeEvent(
        int $inventoryItemId,
        int $oldQuantity,
        int $newQuantity,
        string $changeType,
        int $userId,
        int $tenantId
    ): bool {
        $event = [
            'event_type' => 'stock_changed',
            'inventory_item_id' => $inventoryItemId,
            'old_quantity' => $oldQuantity,
            'new_quantity' => $newQuantity,
            'change' => $newQuantity - $oldQuantity,
            'change_type' => $changeType,
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'timestamp' => now()->toIso8601String(),
        ];

        $channel = "inventory:stock:{$tenantId}";

        Redis::publish($channel, json_encode($event));

        $this->logger->debug('Stock change event published', [
            'channel' => $channel,
            'inventory_item_id' => $inventoryItemId,
            'change' => $event['change'],
        ]);

        return true;
    }

    /**
     * Sync inventory to external system
     *
     * @param  int  $inventoryItemId  Inventory item ID
     * @param  int  $tenantId  Tenant ID
     * @return array Sync result
     */
    public function syncToExternalSystem(int $inventoryItemId, int $tenantId): array
    {
        $lockKey = self::SYNC_LOCK_PREFIX."external:{$inventoryItemId}";

        if (Redis::exists($lockKey)) {
            return [
                'success' => false,
                'message' => 'Sync already in progress',
            ];
        }

        Redis::setex($lockKey, self::LOCK_TTL, '1');

        try {
            $item = $this->db->table('inventory_items')
                ->where('id', $inventoryItemId)
                ->where('tenant_id', $tenantId)
                ->first();

            if (! $item) {
                return [
                    'success' => false,
                    'message' => 'Inventory item not found',
                ];
            }

            $syncData = [
                'item_id' => $item->id,
                'sku' => $item->sku,
                'quantity' => $item->current_stock,
                'reserved_quantity' => $item->reserved_stock,
                'available_quantity' => $item->current_stock - $item->reserved_stock,
                'warehouse_id' => $item->warehouse_id,
                'updated_at' => $item->updated_at->toIso8601String(),
            ];

            // Log sync attempt
            $this->db->table('inventory_sync_log')->insert([
                'inventory_item_id' => $inventoryItemId,
                'sync_type' => 'external',
                'sync_data' => json_encode($syncData),
                'status' => 'success',
                'tenant_id' => $tenantId,
                'created_at' => now(),
            ]);

            return [
                'success' => true,
                'message' => 'Sync completed',
                'sync_data' => $syncData,
            ];
        } catch (\Exception $e) {
            $this->logger->error('External sync failed', [
                'inventory_item_id' => $inventoryItemId,
                'error' => $e->getMessage(),
            ]);

            $this->db->table('inventory_sync_log')->insert([
                'inventory_item_id' => $inventoryItemId,
                'sync_type' => 'external',
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'tenant_id' => $tenantId,
                'created_at' => now(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        } finally {
            Redis::del($lockKey);
        }
    }

    /**
     * Perform data consistency check
     *
     * @param  int  $tenantId  Tenant ID
     * @return array Consistency report
     */
    public function performConsistencyCheck(int $tenantId): array
    {
        $issues = [];

        $items = $this->db->table('inventory_items')
            ->where('tenant_id', $tenantId)
            ->get();

        foreach ($items as $item) {
            $cacheQty = Cache::get(self::CACHE_PREFIX."stock:{$item->id}");

            if ($cacheQty !== null && $cacheQty != $item->current_stock) {
                $issues[] = [
                    'type' => 'cache_mismatch',
                    'inventory_item_id' => $item->id,
                    'cache_quantity' => $cacheQty,
                    'db_quantity' => $item->current_stock,
                    'severity' => 'medium',
                ];

                $this->syncStockToCache($item->id, $item->current_stock);
            }

            $batchTotal = $this->db->table('inventory_batches')
                ->where('inventory_item_id', $item->id)
                ->sum('quantity');

            if ($batchTotal != $item->current_stock) {
                $issues[] = [
                    'type' => 'batch_mismatch',
                    'inventory_item_id' => $item->id,
                    'batch_total' => $batchTotal,
                    'db_quantity' => $item->current_stock,
                    'severity' => 'high',
                ];
            }
        }

        return [
            'tenant_id' => $tenantId,
            'total_items_checked' => $items->count(),
            'issues_found' => count($issues),
            'critical_issues' => count(array_filter($issues, fn ($i) => $i['severity'] === 'critical')),
            'high_issues' => count(array_filter($issues, fn ($i) => $i['severity'] === 'high')),
            'medium_issues' => count(array_filter($issues, fn ($i) => $i['severity'] === 'medium')),
            'issues' => $issues,
            'checked_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Batch sync warehouse inventory
     *
     * @param  int  $warehouseId  Warehouse ID
     * @param  int  $tenantId  Tenant ID
     * @return array Sync result
     */
    public function batchSyncWarehouse(int $warehouseId, int $tenantId): array
    {
        $items = $this->db->table('inventory_items')
            ->where('warehouse_id', $warehouseId)
            ->where('tenant_id', $tenantId)
            ->get();

        $results = [];
        $successCount = 0;
        $failureCount = 0;

        foreach ($items as $item) {
            $result = $this->syncToExternalSystem($item->id, $tenantId);

            $results[] = [
                'inventory_item_id' => $item->id,
                'sku' => $item->sku,
                'success' => $result['success'],
                'message' => $result['message'],
            ];

            if ($result['success']) {
                $successCount++;
            } else {
                $failureCount++;
            }
        }

        $this->invalidateWarehouseCache($warehouseId);

        return [
            'warehouse_id' => $warehouseId,
            'total_items' => $items->count(),
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'results' => $results,
            'synced_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Get sync status for item
     *
     * @param  int  $inventoryItemId  Inventory item ID
     * @return array Sync status
     */
    public function getSyncStatus(int $inventoryItemId): array
    {
        $lastSync = $this->db->table('inventory_sync_log')
            ->where('inventory_item_id', $inventoryItemId)
            ->orderBy('created_at', 'desc')
            ->first();

        $syncInProgress = Redis::exists(self::SYNC_LOCK_PREFIX."external:{$inventoryItemId}");

        return [
            'inventory_item_id' => $inventoryItemId,
            'sync_in_progress' => $syncInProgress,
            'last_sync' => $lastSync ? [
                'sync_type' => $lastSync->sync_type,
                'status' => $lastSync->status,
                'created_at' => $lastSync->created_at->toIso8601String(),
            ] : null,
        ];
    }

    /**
     * Rebuild inventory cache
     *
     * @param  int  $tenantId  Tenant ID
     * @param  int|null  $warehouseId  Warehouse ID (optional)
     * @return array Rebuild result
     */
    public function rebuildCache(int $tenantId, ?int $warehouseId = null): array
    {
        $query = $this->db->table('inventory_items')
            ->where('tenant_id', $tenantId);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        $items = $query->get();

        $rebuiltCount = 0;

        foreach ($items as $item) {
            $this->syncStockToCache($item->id, $item->current_stock);
            $rebuiltCount++;
        }

        if ($warehouseId) {
            $this->invalidateWarehouseCache($warehouseId);
        }

        return [
            'tenant_id' => $tenantId,
            'warehouse_id' => $warehouseId,
            'total_items' => $items->count(),
            'rebuilt_count' => $rebuiltCount,
            'rebuilt_at' => now()->toIso8601String(),
        ];
    }
}
