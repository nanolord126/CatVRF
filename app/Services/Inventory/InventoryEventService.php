<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Event;
use Psr\Log\LoggerInterface;

/**
 * Inventory Event Service
 *
 * Manages inventory-related events:
 * - Stock movement events
 * - Low stock alerts
 * - Reorder triggers
 * - Batch expiry alerts
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class InventoryEventService
{
    use WithAuditLogging;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Dispatch stock movement event
     *
     * @param  int  $inventoryItemId  Inventory item ID
     * @param  string  $movementType  Movement type
     * @param  int  $quantity  Quantity
     * @param  string  $reason  Reason
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @return bool Success
     */
    public function dispatchStockMovementEvent(
        int $inventoryItemId,
        string $movementType,
        int $quantity,
        string $reason,
        int $userId,
        int $tenantId
    ): bool {
        $event = [
            'event_type' => 'stock_movement',
            'inventory_item_id' => $inventoryItemId,
            'movement_type' => $movementType,
            'quantity' => $quantity,
            'reason' => $reason,
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'timestamp' => now()->toIso8601String(),
        ];

        Event::dispatch('inventory.stock.movement', $event);

        $this->logger->info('Stock movement event dispatched', $event);

        return true;
    }

    /**
     * Check and dispatch low stock alerts
     *
     * @param  int  $tenantId  Tenant ID
     * @param  int|null  $warehouseId  Warehouse ID (optional)
     * @return array Alerts dispatched
     */
    public function dispatchLowStockAlerts(int $tenantId, ?int $warehouseId = null): array
    {
        $query = $this->db->table('inventory_items')
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->whereColumn('current_stock', '<=', 'min_stock_threshold');

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        $items = $query->get();

        $alertsDispatched = [];

        foreach ($items as $item) {
            $alert = [
                'event_type' => 'low_stock_alert',
                'inventory_item_id' => $item->id,
                'sku' => $item->sku,
                'name' => $item->name,
                'current_stock' => $item->current_stock,
                'min_stock_threshold' => $item->min_stock_threshold,
                'warehouse_id' => $item->warehouse_id,
                'tenant_id' => $tenantId,
                'priority' => $this->calculateLowStockPriority($item),
                'timestamp' => now()->toIso8601String(),
            ];

            Event::dispatch('inventory.low_stock', $alert);

            $alertsDispatched[] = $alert;

            $this->db->table('inventory_alerts')->insert([
                'uuid' => \Illuminate\Support\Str::uuid()->toString(),
                'alert_type' => 'low_stock',
                'inventory_item_id' => $item->id,
                'severity' => $alert['priority'],
                'data' => json_encode($alert),
                'tenant_id' => $tenantId,
                'created_at' => now(),
            ]);
        }

        $this->logger->info('Low stock alerts dispatched', [
            'count' => count($alertsDispatched),
            'tenant_id' => $tenantId,
        ]);

        return $alertsDispatched;
    }

    /**
     * Check and dispatch stockout alerts
     *
     * @param  int  $tenantId  Tenant ID
     * @param  int|null  $warehouseId  Warehouse ID (optional)
     * @return array Alerts dispatched
     */
    public function dispatchStockoutAlerts(int $tenantId, ?int $warehouseId = null): array
    {
        $query = $this->db->table('inventory_items')
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where('current_stock', '<=', 0);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        $items = $query->get();

        $alertsDispatched = [];

        foreach ($items as $item) {
            $alert = [
                'event_type' => 'stockout_alert',
                'inventory_item_id' => $item->id,
                'sku' => $item->sku,
                'name' => $item->name,
                'current_stock' => $item->current_stock,
                'warehouse_id' => $item->warehouse_id,
                'tenant_id' => $tenantId,
                'severity' => 'critical',
                'timestamp' => now()->toIso8601String(),
            ];

            Event::dispatch('inventory.stockout', $alert);

            $alertsDispatched[] = $alert;

            $this->db->table('inventory_alerts')->insert([
                'uuid' => \Illuminate\Support\Str::uuid()->toString(),
                'alert_type' => 'stockout',
                'inventory_item_id' => $item->id,
                'severity' => 'critical',
                'data' => json_encode($alert),
                'tenant_id' => $tenantId,
                'created_at' => now(),
            ]);
        }

        $this->logger->info('Stockout alerts dispatched', [
            'count' => count($alertsDispatched),
            'tenant_id' => $tenantId,
        ]);

        return $alertsDispatched;
    }

    /**
     * Check and dispatch batch expiry alerts
     *
     * @param  int  $tenantId  Tenant ID
     * @param  int  $daysThreshold  Days threshold (default: 30)
     * @return array Alerts dispatched
     */
    public function dispatchBatchExpiryAlerts(int $tenantId, int $daysThreshold = 30): array
    {
        $cutoffDate = now()->addDays($daysThreshold);

        $batches = $this->db->table('inventory_batches as b')
            ->join('inventory_items as i', 'b.inventory_item_id', '=', 'i.id')
            ->where('i.tenant_id', $tenantId)
            ->where('b.expiry_date', '<=', $cutoffDate)
            ->where('b.expiry_date', '>', now())
            ->where('b.quantity', '>', 0)
            ->select('b.*', 'i.sku', 'i.name', 'i.warehouse_id')
            ->get();

        $alertsDispatched = [];

        foreach ($batches as $batch) {
            $daysUntilExpiry = now()->diffInDays($batch->expiry_date);
            $severity = $daysUntilExpiry <= 7 ? 'critical' : ($daysUntilExpiry <= 14 ? 'high' : 'medium');

            $alert = [
                'event_type' => 'batch_expiry_alert',
                'batch_id' => $batch->id,
                'batch_number' => $batch->batch_number,
                'inventory_item_id' => $batch->inventory_item_id,
                'sku' => $batch->sku,
                'name' => $batch->name,
                'quantity' => $batch->quantity,
                'expiry_date' => $batch->expiry_date->toIso8601String(),
                'days_until_expiry' => $daysUntilExpiry,
                'warehouse_id' => $batch->warehouse_id,
                'tenant_id' => $tenantId,
                'severity' => $severity,
                'timestamp' => now()->toIso8601String(),
            ];

            Event::dispatch('inventory.batch_expiry', $alert);

            $alertsDispatched[] = $alert;

            $this->db->table('inventory_alerts')->insert([
                'uuid' => \Illuminate\Support\Str::uuid()->toString(),
                'alert_type' => 'batch_expiry',
                'inventory_item_id' => $batch->inventory_item_id,
                'severity' => $severity,
                'data' => json_encode($alert),
                'tenant_id' => $tenantId,
                'created_at' => now(),
            ]);
        }

        $this->logger->info('Batch expiry alerts dispatched', [
            'count' => count($alertsDispatched),
            'tenant_id' => $tenantId,
            'days_threshold' => $daysThreshold,
        ]);

        return $alertsDispatched;
    }

    /**
     * Dispatch reorder trigger event
     *
     * @param  int  $inventoryItemId  Inventory item ID
     * @param  int  $suggestedOrderQty  Suggested order quantity
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @return bool Success
     */
    public function dispatchReorderTrigger(
        int $inventoryItemId,
        int $suggestedOrderQty,
        int $userId,
        int $tenantId
    ): bool {
        $item = $this->db->table('inventory_items')
            ->where('id', $inventoryItemId)
            ->first();

        if (! $item) {
            return false;
        }

        $event = [
            'event_type' => 'reorder_trigger',
            'inventory_item_id' => $inventoryItemId,
            'sku' => $item->sku,
            'name' => $item->name,
            'current_stock' => $item->current_stock,
            'min_stock_threshold' => $item->min_stock_threshold,
            'suggested_order_quantity' => $suggestedOrderQty,
            'lead_time_days' => $item->lead_time_days ?? 7,
            'preferred_supplier_id' => $item->preferred_supplier_id,
            'warehouse_id' => $item->warehouse_id,
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'timestamp' => now()->toIso8601String(),
        ];

        Event::dispatch('inventory.reorder.trigger', $event);

        $this->logger->info('Reorder trigger event dispatched', $event);

        return true;
    }

    /**
     * Dispatch inventory adjustment event
     *
     * @param  int  $inventoryItemId  Inventory item ID
     * @param  int  $oldQuantity  Old quantity
     * @param  int  $newQuantity  New quantity
     * @param  string  $reason  Adjustment reason
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @return bool Success
     */
    public function dispatchAdjustmentEvent(
        int $inventoryItemId,
        int $oldQuantity,
        int $newQuantity,
        string $reason,
        int $userId,
        int $tenantId
    ): bool {
        $event = [
            'event_type' => 'inventory_adjustment',
            'inventory_item_id' => $inventoryItemId,
            'old_quantity' => $oldQuantity,
            'new_quantity' => $newQuantity,
            'adjustment' => $newQuantity - $oldQuantity,
            'reason' => $reason,
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'timestamp' => now()->toIso8601String(),
        ];

        Event::dispatch('inventory.adjustment', $event);

        $this->logger->info('Inventory adjustment event dispatched', $event);

        return true;
    }

    /**
     * Get active alerts for tenant
     *
     * @param  int  $tenantId  Tenant ID
     * @param  int  $limit  Limit (default: 100)
     * @return array Active alerts
     */
    public function getActiveAlerts(int $tenantId, int $limit = 100): array
    {
        $alerts = $this->db->table('inventory_alerts')
            ->where('tenant_id', $tenantId)
            ->where('acknowledged', false)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return $alerts->map(fn ($alert) => [
            'id' => $alert->id,
            'alert_type' => $alert->alert_type,
            'severity' => $alert->severity,
            'inventory_item_id' => $alert->inventory_item_id,
            'data' => json_decode($alert->data, true),
            'created_at' => $alert->created_at->toIso8601String(),
        ])->toArray();
    }

    /**
     * Acknowledge alert
     *
     * @param  int  $alertId  Alert ID
     * @param  int  $userId  User ID
     * @return bool Success
     */
    public function acknowledgeAlert(int $alertId, int $userId): bool
    {
        $this->db->table('inventory_alerts')
            ->where('id', $alertId)
            ->update([
                'acknowledged' => true,
                'acknowledged_by' => $userId,
                'acknowledged_at' => now(),
            ]);

        $this->logger->info('Alert acknowledged', [
            'alert_id' => $alertId,
            'user_id' => $userId,
        ]);

        return true;
    }

    /**
     * Calculate low stock priority
     *
     * @param  mixed  $item  Inventory item
     * @return string Priority
     */
    private function calculateLowStockPriority($item): string
    {
        $stockRatio = $item->current_stock / max(1, $item->min_stock_threshold);

        if ($stockRatio <= 0) {
            return 'critical';
        }

        if ($stockRatio <= 0.25) {
            return 'high';
        }

        if ($stockRatio <= 0.5) {
            return 'medium';
        }

        return 'low';
    }
}
