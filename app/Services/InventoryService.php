<?php declare(strict_types=1);

namespace App\Services;



use App\Services\FraudControlService;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;

/**
 * Inventory Service
 * Production 2026 CANON
 *
 * Manages inventory operations atomically:
 * - Decrease inventory on service completion
 * - Increase inventory on stock replenishment
 * - Check availability before operations
 * - Track inventory movements with audit trail
 *
 * @author CatVRF Team
 * @version 2026.03.24
 */
final class InventoryService
{
    public function __construct(
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * Decrease inventory (consumable deduction)
     * Used when service is completed or product is sold
     *
     * @param int $inventoryItemId Item ID
     * @param int $quantity Quantity to decrease
     * @param string $reason Deduction reason
     * @param string $sourceType Source type (beauty_appointment, food_order, etc.)
     * @param int $sourceId Source ID (appointment_id, order_id, etc.)
     * @param string $correlationId Tracing ID
     * @return bool
     * @throws \Exception If insufficient inventory
     */
    public static function decreaseInventory(
        int $inventoryItemId,
        int $quantity,
        string $reason,
        string $sourceType,
        int $sourceId,
        string $correlationId
    ): bool {
        $this->fraud->check(new \stdClass());
        return $this->db->transaction(function () use ($inventoryItemId, $quantity, $reason, $sourceType, $sourceId, $correlationId) {
            $item = $this->db->table('inventory_items')
                ->where('id', $inventoryItemId)
                ->lockForUpdate()
                ->first();

            if (!$item) {
                throw new \RuntimeException("Inventory item not found: {$inventoryItemId}");
            }

            if ($item->current_stock < $quantity) {
                throw new \RuntimeException(
                    "Insufficient inventory: {$item->current_stock} < {$quantity} required. Item: {$inventoryItemId}"
                );
            }

            // Deduct from inventory
            $this->db->table('inventory_items')
                ->where('id', $inventoryItemId)
                ->decrement('current_stock', $quantity);

            // Log movement
            $this->db->table('stock_movements')->insert([
                'inventory_item_id' => $inventoryItemId,
                'type' => 'out',
                'quantity' => -$quantity,
                'reason' => $reason,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'correlation_id' => $correlationId,
                'created_at' => now(),
            ]);

            $this->logger->channel('audit')->info('Inventory decreased', [
                'correlation_id' => $correlationId,
                'inventory_item_id' => $inventoryItemId,
                'quantity' => $quantity,
                'reason' => $reason,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
            ]);

            return true;
        });
    }

    /**
     * Increase inventory (replenishment)
     *
     * @param int $inventoryItemId Item ID
     * @param int $quantity Quantity to add
     * @param string $reason Addition reason (purchase, return, correction, etc.)
     * @param string $sourceType Source type (supplier, import, manual, etc.)
     * @param string $correlationId Tracing ID
     * @return bool
     */
    public static function increaseInventory(
        int $inventoryItemId,
        int $quantity,
        string $reason,
        string $sourceType = 'manual',
        string $correlationId = ''
    ): bool {
        return $this->db->transaction(function () use ($inventoryItemId, $quantity, $reason, $sourceType, $correlationId) {
            $item = $this->db->table('inventory_items')
                ->where('id', $inventoryItemId)
                ->lockForUpdate()
                ->first();

            if (!$item) {
                throw new \RuntimeException("Inventory item not found: {$inventoryItemId}");
            }

            // Add to inventory
            $this->db->table('inventory_items')
                ->where('id', $inventoryItemId)
                ->increment('current_stock', $quantity);

            // Log movement
            $this->db->table('stock_movements')->insert([
                'inventory_item_id' => $inventoryItemId,
                'type' => 'in',
                'quantity' => $quantity,
                'reason' => $reason,
                'source_type' => $sourceType,
                'source_id' => null,
                'correlation_id' => $correlationId,
                'created_at' => now(),
            ]);

            $this->logger->channel('audit')->info('Inventory increased', [
                'correlation_id' => $correlationId,
                'inventory_item_id' => $inventoryItemId,
                'quantity' => $quantity,
                'reason' => $reason,
                'source_type' => $sourceType,
            ]);

            return true;
        });
    }

    /**
     * Check inventory availability
     *
     * @param int $inventoryItemId Item ID
     * @param int $requiredQuantity Required quantity
     * @return bool true if available
     */
    public static function checkAvailability(int $inventoryItemId, int $requiredQuantity): bool
    {
        $item = $this->db->table('inventory_items')
            ->where('id', $inventoryItemId)
            ->first();

        if (!$item) {
            return false;
        }

        return $item->current_stock >= $requiredQuantity;
    }

    /**
     * Get current inventory level
     *
     * @param int $inventoryItemId Item ID
     * @return int Current stock quantity
     */
    public static function getInventoryLevel(int $inventoryItemId): int
    {
        $item = $this->db->table('inventory_items')
            ->where('id', $inventoryItemId)
            ->first();

        return $item ? (int)$item->current_stock : 0;
    }

    /**
     * Check if inventory is low
     *
     * @param int $inventoryItemId Item ID
     * @return bool true if current_stock <= min_stock_threshold
     */
    public static function isLow(int $inventoryItemId): bool
    {
        $item = $this->db->table('inventory_items')
            ->where('id', $inventoryItemId)
            ->first();

        if (!$item) {
            return false;
        }

        return $item->current_stock <= $item->min_stock_threshold;
    }

    /**
     * Adjust inventory (correction, physical count discrepancy)
     *
     * @param int $inventoryItemId Item ID
     * @param int $newQuantity New quantity (absolute, not delta)
     * @param string $reason Adjustment reason
     * @param int $userId User ID making adjustment
     * @param string $correlationId Tracing ID
     * @return bool
     */
    public static function adjustInventory(
        int $inventoryItemId,
        int $newQuantity,
        string $reason,
        int $userId,
        string $correlationId
    ): bool {
        return $this->db->transaction(function () use ($inventoryItemId, $newQuantity, $reason, $userId, $correlationId) {
            $item = $this->db->table('inventory_items')
                ->where('id', $inventoryItemId)
                ->lockForUpdate()
                ->first();

            if (!$item) {
                throw new \RuntimeException("Inventory item not found: {$inventoryItemId}");
            }

            $oldQuantity = $item->current_stock;
            $delta = $newQuantity - $oldQuantity;

            // Update inventory
            $this->db->table('inventory_items')
                ->where('id', $inventoryItemId)
                ->update(['current_stock' => $newQuantity]);

            // Log adjustment
            $this->db->table('stock_movements')->insert([
                'inventory_item_id' => $inventoryItemId,
                'type' => 'adjust',
                'quantity' => $delta,
                'reason' => $reason,
                'source_type' => 'manual',
                'source_id' => $userId,
                'correlation_id' => $correlationId,
                'created_at' => now(),
            ]);

            $this->logger->channel('audit')->info('Inventory adjusted', [
                'correlation_id' => $correlationId,
                'inventory_item_id' => $inventoryItemId,
                'old_quantity' => $oldQuantity,
                'new_quantity' => $newQuantity,
                'delta' => $delta,
                'reason' => $reason,
                'user_id' => $userId,
            ]);

            return true;
        });
    }

    /**
     * Get low stock items for tenant
     *
     * @param int $tenantId Tenant ID
     * @return array Low stock inventory items
     */
    public static function getLowStockItems(int $tenantId): array
    {
        return $this->db->table('inventory_items')
            ->where('tenant_id', $tenantId)
            ->whereRaw('current_stock <= min_stock_threshold')
            ->get()
            ->toArray();
    }

    /**
     * Get inventory movement history
     *
     * @param int $inventoryItemId Item ID
     * @param int $limit Limit results
     * @return array Movement records
     */
    public static function getMovementHistory(int $inventoryItemId, int $limit = 50): array
    {
        return $this->db->table('stock_movements')
            ->where('inventory_item_id', $inventoryItemId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    // ─────────────────────────────────────────────────────────────
    // Instance methods for Cart / Reservation integration
    // ─────────────────────────────────────────────────────────────

    /**
     * Получить доступное количество товара (quantity - reserved).
     */
    public function getAvailableStock(int $productId): int
    {
        $item = $this->db->table('inventory_items')
            ->where('product_id', $productId)
            ->first();

        if (!$item) {
            return 0;
        }

        $reserved = $this->db->table('cart_items')
            ->join('carts', 'cart_items.cart_id', '=', 'carts.id')
            ->where('cart_items.product_id', $productId)
            ->where('carts.status', 'active')
            ->where('carts.reserved_until', '>', now())
            ->sum('cart_items.quantity');

        return max(0, (int) $item->current_stock - (int) $reserved);
    }

    /**
     * Зарезервировать товар в корзине (мягкий резерв).
     */
    public function reserve(int $productId, int $quantity, string $sourceType, int $sourceId): void
    {
        $this->db->table('stock_movements')->insert([
            'inventory_item_id' => $this->getItemIdByProduct($productId),
            'type'              => 'reserve',
            'quantity'          => -$quantity,
            'reason'            => 'cart_reserve',
            'source_type'       => $sourceType,
            'source_id'         => $sourceId,
            'correlation_id'    => \Illuminate\Support\Str::uuid()->toString(),
            'created_at'        => now(),
        ]);
    }

    /**
     * Освободить резерв (истечение корзины, удаление позиции).
     */
    public function releaseReserve(int $productId, int $quantity, string $sourceType, int $sourceId): void
    {
        $this->db->table('stock_movements')->insert([
            'inventory_item_id' => $this->getItemIdByProduct($productId),
            'type'              => 'release',
            'quantity'          => $quantity,
            'reason'            => 'cart_reserve_release',
            'source_type'       => $sourceType,
            'source_id'         => $sourceId,
            'correlation_id'    => \Illuminate\Support\Str::uuid()->toString(),
            'created_at'        => now(),
        ]);
    }

    /**
     * Долгосрочный резерв для B2B-заказов (TTL = 7 дней).
     * Возвращает ID резерва в stock_movements.
     */
    public function reserveForB2B(
        int    $productId,
        int    $warehouseId,
        int    $quantity,
        int    $orderId,
        string $correlationId,
    ): int {
        $available = $this->getAvailableStock($productId);

        if ($available < $quantity) {
            throw new \DomainException(
                "Insufficient stock for product #{$productId}: available={$available}, required={$quantity}"
            );
        }

        return (int) $this->db->table('stock_movements')->insertGetId([
            'inventory_item_id' => $this->getItemIdByProduct($productId),
            'type'              => 'reserve',
            'quantity'          => -$quantity,
            'reason'            => 'b2b_order_reserve',
            'source_type'       => 'b2b_order',
            'source_id'         => $orderId,
            'correlation_id'    => $correlationId,
            'created_at'        => now(),
            'expires_at'        => now()->addDays(7),
        ]);
    }

    private function getItemIdByProduct(int $productId): ?int
    {
        return $this->db->table('inventory_items')
            ->where('product_id', $productId)
            ->value('id');
    }
}
