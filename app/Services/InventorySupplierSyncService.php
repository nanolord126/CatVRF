<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\InventoryUpdated;
use App\Models\InventoryItem;
use App\Services\RedisDistributedLockService;
use App\Traits\WithAuditLogging;
use Carbon\CarbonImmutable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

/**
 * Inventory Supplier Synchronization Service
 *
 * Handles stock replenishment from suppliers with:
 * - Automatic reorder when stock falls below threshold
 * - Supplier API integration
 * - Async synchronization via jobs
 * - Distributed locking for concurrent updates
 * - Real-time inventory updates via WebSocket
 *
 * @author CatVRF Team
 * @version 2026.04.26
 */
final readonly class InventorySupplierSyncService
{
    use WithAuditLogging;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly DatabaseManager $db,
        private readonly InventoryService $inventoryService,
        private readonly RedisDistributedLockService $distributedLock,
    ) {}

    /**
     * Check and trigger auto-reorder for low stock items
     *
     * @param  int  $tenantId  Tenant ID
     * @return array{processed: int, reordered: int, errors: array}
     */
    public function checkAndReorderLowStock(int $tenantId): array
    {
        $lowStockItems = InventoryItem::where('tenant_id', $tenantId)
            ->whereColumn('quantity', '<=', 'min_stock_level')
            ->get();

        $processed = 0;
        $reordered = 0;
        $errors = [];

        foreach ($lowStockItems as $item) {
            $processed++;

            try {
                $result = $this->triggerReorder($item);
                if ($result) {
                    $reordered++;
                }
            } catch (\Throwable $e) {
                $errors[] = [
                    'inventory_item_id' => $item->id,
                    'error' => $e->getMessage(),
                ];
                $this->logger->error('Failed to trigger reorder', [
                    'inventory_item_id' => $item->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'processed' => $processed,
            'reordered' => $reordered,
            'errors' => $errors,
        ];
    }

    /**
     * Trigger reorder for a specific inventory item
     *
     * @param  InventoryItem  $item
     * @return bool
     */
    public function triggerReorder(InventoryItem $item): bool
    {
        $lockKey = "inventory:reorder:{$item->id}";

        return $this->distributedLock->withLock($lockKey, function () use ($item) {
            // Check if already has pending reorder
            $hasPending = $this->hasPendingReorder($item->id);
            if ($hasPending) {
                $this->logger->info('Reorder already pending', [
                    'inventory_item_id' => $item->id,
                ]);
                return false;
            }

            // Calculate reorder quantity
            $reorderQty = $this->calculateReorderQuantity($item);

            // Get supplier information
            $supplier = $this->getSupplierForItem($item);
            if (! $supplier) {
                throw new \RuntimeException("No supplier configured for item: {$item->id}");
            }

            // Create reorder record
            $reorderId = $this->createReorderRecord($item, $supplier, $reorderQty);

            // Send to supplier API
            $this->sendToSupplierAPI($item, $supplier, $reorderQty, $reorderId);

            // Log the action
            $this->logAction(
                action: 'inventory_reorder_triggered',
                entityType: 'InventoryItem',
                entityId: $item->id,
                context: [
                    'current_quantity' => $item->quantity,
                    'min_stock_level' => $item->min_stock_level,
                    'reorder_quantity' => $reorderQty,
                    'supplier_id' => $supplier['id'],
                    'reorder_id' => $reorderId,
                ],
                userId: null,
                tenantId: $item->tenant_id
            );

            return true;
        }, ttl: 60);
    }

    /**
     * Synchronize stock from supplier
     *
     * Called when supplier confirms shipment or delivery
     *
     * @param  int  $inventoryItemId
     * @param  int  $receivedQuantity
     * @param  string  $supplierReference
     * @param  string  $correlationId
     * @return bool
     */
    public function syncStockFromSupplier(
        int $inventoryItemId,
        int $receivedQuantity,
        string $supplierReference,
        string $correlationId = ''
    ): bool {
        $correlationId = $correlationId ?: uniqid('supplier_sync_', true);

        return $this->db->transaction(function () use ($inventoryItemId, $receivedQuantity, $supplierReference, $correlationId) {
            $item = InventoryItem::lockForUpdate()->find($inventoryItemId);

            if (! $item) {
                throw new \RuntimeException("Inventory item not found: {$inventoryItemId}");
            }

            // Increase inventory
            $this->inventoryService->increaseInventory(
                $inventoryItemId,
                $receivedQuantity,
                'supplier_replenishment',
                'supplier',
                $correlationId
            );

            // Update reorder record
            $this->updateReorderRecord($inventoryItemId, $supplierReference, $receivedQuantity);

            // Log the synchronization
            $this->logAction(
                action: 'inventory_synced_from_supplier',
                entityType: 'InventoryItem',
                entityId: $inventoryItemId,
                context: [
                    'received_quantity' => $receivedQuantity,
                    'supplier_reference' => $supplierReference,
                    'correlation_id' => $correlationId,
                    'new_quantity' => $item->quantity + $receivedQuantity,
                ],
                userId: null,
                tenantId: $item->tenant_id
            );

            // Broadcast real-time update
            event(new InventoryUpdated($inventoryItemId, $item->tenant_id, [
                'quantity' => $item->quantity + $receivedQuantity,
                'supplier_reference' => $supplierReference,
            ]));

            return true;
        });
    }

    /**
     * Calculate reorder quantity based on item settings
     */
    private function calculateReorderQuantity(InventoryItem $item): int
    {
        // Default: reorder to max_stock_level or 2x min_stock_level
        $targetLevel = $item->max_stock_level ?? ($item->min_stock_level * 2);
        $reorderQty = $targetLevel - $item->quantity;

        return max($reorderQty, $item->min_stock_level);
    }

    /**
     * Get supplier configuration for an item
     */
    private function getSupplierForItem(InventoryItem $item): ?array
    {
        // This would typically come from a suppliers table or configuration
        // For now, return a mock supplier
        return $this->db->table('inventory_suppliers')
            ->where('sku', $item->sku)
            ->first();
    }

    /**
     * Check if item has pending reorder
     */
    private function hasPendingReorder(int $inventoryItemId): bool
    {
        return $this->db->table('inventory_reorders')
            ->where('inventory_item_id', $inventoryItemId)
            ->whereIn('status', ['pending', 'processing'])
            ->exists();
    }

    /**
     * Create reorder record
     */
    private function createReorderRecord(InventoryItem $item, array $supplier, int $quantity): int
    {
        return $this->db->table('inventory_reorders')->insertGetId([
            'inventory_item_id' => $item->id,
            'tenant_id' => $item->tenant_id,
            'supplier_id' => $supplier['id'],
            'quantity' => $quantity,
            'status' => 'pending',
            'reference' => uniqid('REORDER-', true),
            'created_at' => CarbonImmutable::now(),
            'updated_at' => CarbonImmutable::now(),
        ]);
    }

    /**
     * Update reorder record after supplier response
     */
    private function updateReorderRecord(int $inventoryItemId, string $supplierReference, int $receivedQuantity): void
    {
        $this->db->table('inventory_reorders')
            ->where('inventory_item_id', $inventoryItemId)
            ->where('status', 'pending')
            ->update([
                'status' => 'completed',
                'supplier_reference' => $supplierReference,
                'received_quantity' => $receivedQuantity,
                'completed_at' => CarbonImmutable::now(),
                'updated_at' => CarbonImmutable::now(),
            ]);
    }

    /**
     * Send reorder request to supplier API
     */
    private function sendToSupplierAPI(InventoryItem $item, array $supplier, int $quantity, int $reorderId): void
    {
        if (empty($supplier['api_endpoint'])) {
            $this->logger->warning('No API endpoint configured for supplier', [
                'supplier_id' => $supplier['id'],
            ]);
            return;
        }

        try {
            $response = Http::timeout(10)->post($supplier['api_endpoint'], [
                'sku' => $item->sku,
                'quantity' => $quantity,
                'reference' => "REORDER-{$reorderId}",
                'warehouse_id' => $item->warehouse_id,
            ]);

            if (! $response->successful()) {
                throw new \RuntimeException("Supplier API error: {$response->status()}");
            }

            $this->logger->info('Supplier API call successful', [
                'supplier_id' => $supplier['id'],
                'sku' => $item->sku,
                'quantity' => $quantity,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to call supplier API', [
                'supplier_id' => $supplier['id'],
                'error' => $e->getMessage(),
            ]);
            // Update reorder status to failed
            $this->db->table('inventory_reorders')
                ->where('id', $reorderId)
                ->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'updated_at' => CarbonImmutable::now(),
                ]);
            throw $e;
        }
    }

    /**
     * Get supplier inventory levels (for synchronization)
     *
     * @param  int  $supplierId
     * @return array
     */
    public function getSupplierInventoryLevels(int $supplierId): array
    {
        $supplier = $this->db->table('inventory_suppliers')->find($supplierId);

        if (! $supplier || empty($supplier['api_endpoint'])) {
            return [];
        }

        try {
            $response = Http::timeout(30)->get($supplier['api_endpoint'] . '/inventory');

            if (! $response->successful()) {
                throw new \RuntimeException("Supplier API error: {$response->status()}");
            }

            return $response->json();
        } catch (\Throwable $e) {
            $this->logger->error('Failed to get supplier inventory', [
                'supplier_id' => $supplierId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Invalidate supplier cache
     */
    public function invalidateSupplierCache(int $supplierId): void
    {
        Cache::tags(['supplier', "supplier:{$supplierId}"])->flush();
    }
}
