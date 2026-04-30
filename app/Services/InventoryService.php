<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\OptimisticLockException;
use App\Models\InventoryItem;
use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;
use Carbon\CarbonImmutable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Inventory Service
 * Production 2026 CANON
 *
 * Manages inventory operations with optimistic locking:
 * - Decrease inventory on service completion
 * - Increase inventory on stock replenishment
 * - Check availability before operations
 * - Track inventory movements with audit trail
 * - Optimistic locking for concurrent updates
 * - Distributed locking for reservations
 *
 * @author CatVRF Team
 * @version 2026.04.26
 */
final readonly class InventoryService
{
    use WithAuditLogging;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly DatabaseManager $db,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Decrease inventory with optimistic locking
     *
     * @param  int  $inventoryItemId  Item ID
     * @param  int  $quantity  Quantity to decrease
     * @param  string  $reason  Deduction reason
     * @param  string  $sourceType  Source type (beauty_appointment, food_order, etc.)
     * @param  int  $sourceId  Source ID (appointment_id, order_id, etc.)
     * @param  string  $correlationId  Tracing ID
     * @throws \Exception If insufficient inventory or optimistic lock fails
     */
    public function decreaseInventory(
        int $inventoryItemId,
        int $quantity,
        string $reason,
        string $sourceType,
        int $sourceId,
        string $correlationId = ''
    ): bool {
        $correlationId = $correlationId ?: Str::uuid()->toString();

        return $this->db->transaction(function () use ($inventoryItemId, $quantity, $reason, $sourceType, $sourceId, $correlationId) {
            $item = InventoryItem::lockForUpdate()->find($inventoryItemId);

            if (! $item) {
                throw new \RuntimeException("Inventory item not found: {$inventoryItemId}");
            }

            if ($item->quantity < $quantity) {
                throw new \RuntimeException(
                    "Insufficient inventory: {$item->quantity} < {$quantity} required. Item: {$inventoryItemId}"
                );
            }

            $oldQuantity = $item->quantity;
            $newQuantity = $item->quantity - $quantity;

            // Update with optimistic locking
            $affected = $this->db->table('inventory_items')
                ->where('id', $inventoryItemId)
                ->where('version', $item->version)
                ->update([
                    'quantity' => $newQuantity,
                    'version' => $item->version + 1,
                    'updated_at' => now(),
                ]);

            if ($affected === 0) {
                throw new OptimisticLockException(
                    entityType: 'inventory_item',
                    entityId: $inventoryItemId,
                    expectedVersion: $item->version,
                    actualVersion: $this->getCurrentVersion($inventoryItemId)
                );
            }

            // Log movement
            $movementId = $this->db->table('stock_movements')->insertGetId([
                'inventory_item_id' => $inventoryItemId,
                'type' => 'out',
                'quantity' => -$quantity,
                'reason' => $reason,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'correlation_id' => $correlationId,
                'created_at' => CarbonImmutable::now(),
            ]);

            $this->logAction(
                action: 'inventory_decreased',
                entityType: 'InventoryItem',
                entityId: $inventoryItemId,
                context: [
                    'correlation_id' => $correlationId,
                    'quantity' => $quantity,
                    'old_quantity' => $oldQuantity,
                    'new_quantity' => $newQuantity,
                    'reason' => $reason,
                    'source_type' => $sourceType,
                    'source_id' => $sourceId,
                    'movement_id' => $movementId,
                ],
                userId: null,
                tenantId: $item->tenant_id
            );

            // Invalidate cache
            $this->invalidateInventoryCache($inventoryItemId);

            return true;
        });
    }

    /**
     * Increase inventory with optimistic locking
     *
     * @param  int  $inventoryItemId  Item ID
     * @param  int  $quantity  Quantity to add
     * @param  string  $reason  Addition reason (purchase, return, correction, etc.)
     * @param  string  $sourceType  Source type (supplier, import, manual, etc.)
     * @param  string  $correlationId  Tracing ID
     */
    public function increaseInventory(
        int $inventoryItemId,
        int $quantity,
        string $reason,
        string $sourceType = 'manual',
        string $correlationId = ''
    ): bool {
        $correlationId = $correlationId ?: Str::uuid()->toString();

        return $this->db->transaction(function () use ($inventoryItemId, $quantity, $reason, $sourceType, $correlationId) {
            $item = InventoryItem::lockForUpdate()->find($inventoryItemId);

            if (! $item) {
                throw new \RuntimeException("Inventory item not found: {$inventoryItemId}");
            }

            $oldQuantity = $item->quantity;
            $newQuantity = $item->quantity + $quantity;

            // Update with optimistic locking
            $affected = $this->db->table('inventory_items')
                ->where('id', $inventoryItemId)
                ->where('version', $item->version)
                ->update([
                    'quantity' => $newQuantity,
                    'version' => $item->version + 1,
                    'updated_at' => now(),
                ]);

            if ($affected === 0) {
                throw new OptimisticLockException(
                    entityType: 'inventory_item',
                    entityId: $inventoryItemId,
                    expectedVersion: $item->version,
                    actualVersion: $this->getCurrentVersion($inventoryItemId)
                );
            }

            // Log movement
            $movementId = $this->db->table('stock_movements')->insertGetId([
                'inventory_item_id' => $inventoryItemId,
                'type' => 'in',
                'quantity' => $quantity,
                'reason' => $reason,
                'source_type' => $sourceType,
                'source_id' => null,
                'correlation_id' => $correlationId,
                'created_at' => CarbonImmutable::now(),
            ]);

            $this->logAction(
                action: 'inventory_increased',
                entityType: 'InventoryItem',
                entityId: $inventoryItemId,
                context: [
                    'correlation_id' => $correlationId,
                    'quantity' => $quantity,
                    'old_quantity' => $oldQuantity,
                    'new_quantity' => $newQuantity,
                    'reason' => $reason,
                    'source_type' => $sourceType,
                    'movement_id' => $movementId,
                ],
                userId: null,
                tenantId: $item->tenant_id
            );

            // Invalidate cache
            $this->invalidateInventoryCache($inventoryItemId);

            return true;
        });
    }

    /**
     * Check inventory availability (cached)
     *
     * @param  int  $inventoryItemId  Item ID
     * @param  int  $requiredQuantity  Required quantity
     * @return bool true if available
     */
    public function checkAvailability(int $inventoryItemId, int $requiredQuantity): bool
    {
        $cacheKey = "inventory:availability:{$inventoryItemId}";
        
        return Cache::remember($cacheKey, 30, function () use ($inventoryItemId, $requiredQuantity) {
            $item = InventoryItem::find($inventoryItemId);
            
            if (! $item) {
                return false;
            }
            
            return $item->quantity >= $requiredQuantity;
        });
    }

    /**
     * Get current inventory level (cached)
     *
     * @param  int  $inventoryItemId  Item ID
     * @return int Current stock quantity
     */
    public function getInventoryLevel(int $inventoryItemId): int
    {
        $cacheKey = "inventory:level:{$inventoryItemId}";
        
        return Cache::remember($cacheKey, 30, function () use ($inventoryItemId) {
            $item = InventoryItem::find($inventoryItemId);
            
            return $item ? (int) $item->quantity : 0;
        });
    }

    /**
     * Check if inventory is low
     *
     * @param  int  $inventoryItemId  Item ID
     * @return bool true if quantity <= min_stock_level
     */
    public function isLow(int $inventoryItemId): bool
    {
        $item = InventoryItem::find($inventoryItemId);
        
        if (! $item) {
            return false;
        }
        
        return $item->quantity <= $item->min_stock_level;
    }

    /**
     * Adjust inventory to absolute quantity with optimistic locking
     *
     * @param  int  $inventoryItemId  Item ID
     * @param  int  $newQuantity  New quantity (absolute, not delta)
     * @param  string  $reason  Adjustment reason
     * @param  int  $userId  User performing adjustment
     * @param  string  $correlationId  Tracing ID
     * @throws OptimisticLockException
     */
    public function adjustInventory(
        int $inventoryItemId,
        int $newQuantity,
        string $reason,
        int $userId,
        string $correlationId = ''
    ): bool {
        $correlationId = $correlationId ?: Str::uuid()->toString();

        return $this->db->transaction(function () use ($inventoryItemId, $newQuantity, $reason, $userId, $correlationId) {
            $item = InventoryItem::lockForUpdate()->find($inventoryItemId);

            if (! $item) {
                throw new \RuntimeException("Inventory item not found: {$inventoryItemId}");
            }

            $oldQuantity = $item->quantity;
            $delta = $newQuantity - $oldQuantity;

            // Update with optimistic locking
            $affected = $this->db->table('inventory_items')
                ->where('id', $inventoryItemId)
                ->where('version', $item->version)
                ->update([
                    'quantity' => $newQuantity,
                    'version' => $item->version + 1,
                    'updated_at' => now(),
                ]);

            if ($affected === 0) {
                throw new OptimisticLockException(
                    entityType: 'inventory_item',
                    entityId: $inventoryItemId,
                    expectedVersion: $item->version,
                    actualVersion: $this->getCurrentVersion($inventoryItemId)
                );
            }

            // Log adjustment
            $movementId = $this->db->table('stock_movements')->insertGetId([
                'inventory_item_id' => $inventoryItemId,
                'type' => 'adjust',
                'quantity' => $delta,
                'reason' => $reason,
                'source_type' => 'manual',
                'source_id' => $userId,
                'correlation_id' => $correlationId,
                'created_at' => CarbonImmutable::now(),
            ]);

            $this->logAction(
                action: 'inventory_adjusted',
                entityType: 'InventoryItem',
                entityId: $inventoryItemId,
                context: [
                    'correlation_id' => $correlationId,
                    'old_quantity' => $oldQuantity,
                    'new_quantity' => $newQuantity,
                    'delta' => $delta,
                    'reason' => $reason,
                    'user_id' => $userId,
                    'movement_id' => $movementId,
                ],
                userId: $userId,
                tenantId: $item->tenant_id
            );

            // Invalidate cache
            $this->invalidateInventoryCache($inventoryItemId);

            return true;
        });
    }

    /**
     * Reserve inventory with distributed lock (for concurrent reservations)
     *
     * @param  int  $inventoryItemId  Item ID
     * @param  int  $quantity  Quantity to reserve
     * @param  string  $sourceType  Source type
     * @param  int  $sourceId  Source ID
     * @param  int  $ttl  Lock TTL in seconds (default: 300 = 5 minutes)
     * @return bool
     */
    public function reserveWithDistributedLock(
        int $inventoryItemId,
        int $quantity,
        string $sourceType,
        int $sourceId,
        int $ttl = 300
    ): bool {
        $lockKey = "inventory:lock:{$inventoryItemId}";
        $correlationId = Str::uuid()->toString();

        // Try to acquire distributed lock
        $lock = Redis::set($lockKey, $correlationId, 'EX', $ttl, 'NX');
        
        if (! $lock) {
            throw new \RuntimeException("Could not acquire lock for inventory item: {$inventoryItemId}");
        }

        try {
            return $this->db->transaction(function () use ($inventoryItemId, $quantity, $sourceType, $sourceId, $correlationId) {
                $item = InventoryItem::lockForUpdate()->find($inventoryItemId);

                if (! $item) {
                    throw new \RuntimeException("Inventory item not found: {$inventoryItemId}");
                }

                $available = $item->quantity - $item->reserved;

                if ($available < $quantity) {
                    throw new \RuntimeException(
                        "Insufficient available inventory: {$available} < {$quantity} required. Item: {$inventoryItemId}"
                    );
                }

                // Update with optimistic locking
                $affected = $this->db->table('inventory_items')
                    ->where('id', $inventoryItemId)
                    ->where('version', $item->version)
                    ->update([
                        'reserved' => $item->reserved + $quantity,
                        'version' => $item->version + 1,
                        'updated_at' => now(),
                    ]);

                if ($affected === 0) {
                    throw new OptimisticLockException(
                        entityType: 'inventory_item',
                        entityId: $inventoryItemId,
                        expectedVersion: $item->version,
                        actualVersion: $this->getCurrentVersion($inventoryItemId)
                    );
                }

                // Log reservation
                $this->logAction(
                    action: 'inventory_reserved',
                    entityType: 'InventoryItem',
                    entityId: $inventoryItemId,
                    context: [
                        'correlation_id' => $correlationId,
                        'quantity' => $quantity,
                        'source_type' => $sourceType,
                        'source_id' => $sourceId,
                    ],
                    userId: null,
                    tenantId: $item->tenant_id
                );

                // Invalidate cache
                $this->invalidateInventoryCache($inventoryItemId);

                return true;
            });
        } finally {
            // Release lock
            Redis::del($lockKey);
        }
    }

    /**
     * Get low stock items for tenant
     *
     * @param  int  $tenantId  Tenant ID
     * @return array Low stock inventory items
     */
    public function getLowStockItems(int $tenantId): array
    {
        return InventoryItem::where('tenant_id', $tenantId)
            ->whereRaw('quantity <= min_stock_level')
            ->get()
            ->toArray();
    }

    /**
     * Get inventory movement history
     *
     * @param  int  $inventoryItemId  Item ID
     * @param  int  $limit  Limit results
     * @return array Movement records
     */
    public function getMovementHistory(int $inventoryItemId, int $limit = 50): array
    {
        return $this->db->table('stock_movements')
            ->where('inventory_item_id', $inventoryItemId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get current version from database
     */
    private function getCurrentVersion(int $inventoryItemId): int
    {
        $item = $this->db->table('inventory_items')
            ->where('id', $inventoryItemId)
            ->value('version');
        
        return (int) $item;
    }

    /**
     * Invalidate inventory cache
     */
    private function invalidateInventoryCache(int $inventoryItemId): void
    {
        Cache::forget("inventory:availability:{$inventoryItemId}");
        Cache::forget("inventory:level:{$inventoryItemId}");
        Cache::tags(['inventory'])->flush();
    }
}
