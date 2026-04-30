<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Services\Security\AuditService;
use App\Services\FraudControl\FraudControlService;
use App\Traits\WithAuditLogging;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Warehouse Transfer Service
 *
 * Manages stock transfers between warehouses:
 * - Create transfer orders
 * - Execute transfers with reservation
 * - Track in-transit inventory
 * - Handle transfer confirmations
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class WarehouseTransferService
{
    use WithAuditLogging;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService,
        private readonly FraudControlService $fraudService,
        private readonly StockReservationService $reservationService,
    ) {}

    /**
     * Create transfer order
     *
     * @param  int  $fromWarehouseId  Source warehouse ID
     * @param  int  $toWarehouseId  Destination warehouse ID
     * @param  array<array<string, mixed>>  $items  Items to transfer
     * @param  string  $reason  Transfer reason
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @return int Transfer order ID
     */
    public function createTransferOrder(
        int $fromWarehouseId,
        int $toWarehouseId,
        array $items,
        string $reason,
        int $userId,
        int $tenantId
    ): int {
        $correlationId = Str::uuid()->toString();

        return $this->db->transaction(function () use (
            $fromWarehouseId,
            $toWarehouseId,
            $items,
            $reason,
            $userId,
            $tenantId,
            $correlationId
        ) {
            if ($fromWarehouseId === $toWarehouseId) {
                throw new \RuntimeException('Source and destination warehouses cannot be the same');
            }

            $totalItems = count($items);
            $totalQuantity = array_sum(array_column($items, 'quantity'));

            // Fraud check for large transfers
            if ($totalQuantity > 1000) {
                $this->fraudService->check([
                    'operation_type' => 'large_warehouse_transfer',
                    'from_warehouse_id' => $fromWarehouseId,
                    'to_warehouse_id' => $toWarehouseId,
                    'total_quantity' => $totalQuantity,
                    'user_id' => $userId,
                    'tenant_id' => $tenantId,
                    'correlation_id' => $correlationId,
                ]);
            }

            $transferOrderId = $this->db->table('warehouse_transfer_orders')->insertGetId([
                'uuid' => Str::uuid()->toString(),
                'transfer_number' => $this->generateTransferNumber(),
                'from_warehouse_id' => $fromWarehouseId,
                'to_warehouse_id' => $toWarehouseId,
                'status' => 'pending',
                'reason' => $reason,
                'total_items' => $totalItems,
                'total_quantity' => $totalQuantity,
                'tenant_id' => $tenantId,
                'created_by' => $userId,
                'created_at' => now(),
            ]);

            foreach ($items as $item) {
                $this->db->table('warehouse_transfer_items')->insert([
                    'transfer_order_id' => $transferOrderId,
                    'inventory_item_id' => $item['inventory_item_id'],
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'] ?? 0,
                    'tenant_id' => $tenantId,
                    'created_at' => now(),
                ]);
            }

            $this->logCreated(
                entityType: 'WarehouseTransferOrder',
                entityId: $transferOrderId,
                context: [
                    'correlation_id' => $correlationId,
                    'transfer_number' => $this->generateTransferNumber(),
                    'from_warehouse_id' => $fromWarehouseId,
                    'to_warehouse_id' => $toWarehouseId,
                    'total_quantity' => $totalQuantity,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return $transferOrderId;
        });
    }

    /**
     * Execute transfer order
     *
     * @param  int  $transferOrderId  Transfer order ID
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @return bool Success
     */
    public function executeTransferOrder(int $transferOrderId, int $userId, int $tenantId): bool
    {
        $correlationId = Str::uuid()->toString();

        return $this->db->transaction(function () use (
            $transferOrderId,
            $userId,
            $tenantId,
            $correlationId
        ) {
            $transferOrder = $this->db->table('warehouse_transfer_orders')
                ->where('id', $transferOrderId)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->first();

            if (! $transferOrder) {
                throw new \RuntimeException("Pending transfer order {$transferOrderId} not found");
            }

            $transferItems = $this->db->table('warehouse_transfer_items')
                ->where('transfer_order_id', $transferOrderId)
                ->get();

            $reservationIds = [];

            foreach ($transferItems as $item) {
                $reservationId = $this->reservationService->reserveStock(
                    $item->inventory_item_id,
                    $item->quantity,
                    'warehouse_transfer',
                    $transferOrderId,
                    $userId,
                    $tenantId,
                    3600 // 1 hour TTL for transfers
                );

                $reservationIds[] = $reservationId;
            }

            $this->db->table('warehouse_transfer_orders')
                ->where('id', $transferOrderId)
                ->update([
                    'status' => 'in_transit',
                    'shipped_at' => now(),
                    'shipped_by' => $userId,
                ]);

            $this->logAction(
                action: 'transfer_order_executed',
                entityType: 'WarehouseTransferOrder',
                entityId: $transferOrderId,
                context: [
                    'correlation_id' => $correlationId,
                    'reservation_ids' => $reservationIds,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return true;
        });
    }

    /**
     * Confirm transfer receipt
     *
     * @param  int  $transferOrderId  Transfer order ID
     * @param  array<array<string, mixed>>  $receivedItems  Received items with quantities
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @return bool Success
     */
    public function confirmTransferReceipt(
        int $transferOrderId,
        array $receivedItems,
        int $userId,
        int $tenantId
    ): bool {
        $correlationId = Str::uuid()->toString();

        return $this->db->transaction(function () use (
            $transferOrderId,
            $receivedItems,
            $userId,
            $tenantId,
            $correlationId
        ) {
            $transferOrder = $this->db->table('warehouse_transfer_orders')
                ->where('id', $transferOrderId)
                ->where('status', 'in_transit')
                ->lockForUpdate()
                ->first();

            if (! $transferOrder) {
                throw new \RuntimeException("In-transit transfer order {$transferOrderId} not found");
            }

            $transferItems = $this->db->table('warehouse_transfer_items')
                ->where('transfer_order_id', $transferOrderId)
                ->get()
                ->keyBy('inventory_item_id');

            $discrepancies = [];

            foreach ($receivedItems as $received) {
                $itemId = $received['inventory_item_id'];
                $receivedQty = $received['quantity'];
                $expectedQty = $transferItems[$itemId]->quantity ?? 0;

                if ($receivedQty !== $expectedQty) {
                    $discrepancies[] = [
                        'inventory_item_id' => $itemId,
                        'expected' => $expectedQty,
                        'received' => $receivedQty,
                        'difference' => $receivedQty - $expectedQty,
                    ];
                }

                // Add stock to destination warehouse
                $this->addStockToWarehouse(
                    $itemId,
                    $transferOrder->to_warehouse_id,
                    $receivedQty,
                    $tenantId
                );

                // Confirm reservation
                $reservation = $this->db->table('stock_reservations')
                    ->where('order_type', 'warehouse_transfer')
                    ->where('order_id', $transferOrderId)
                    ->where('inventory_item_id', $itemId)
                    ->where('status', 'active')
                    ->first();

                if ($reservation) {
                    $this->reservationService->confirmReservation(
                        $reservation->reservation_id,
                        'transfer_out',
                        "Transfer to warehouse {$transferOrder->to_warehouse_id}",
                        $userId,
                        $tenantId
                    );
                }
            }

            $status = empty($discrepancies) ? 'completed' : 'completed_with_discrepancies';

            $this->db->table('warehouse_transfer_orders')
                ->where('id', $transferOrderId)
                ->update([
                    'status' => $status,
                    'received_at' => now(),
                    'received_by' => $userId,
                    'discrepancies' => ! empty($discrepancies) ? json_encode($discrepancies) : null,
                ]);

            $this->logAction(
                action: 'transfer_receipt_confirmed',
                entityType: 'WarehouseTransferOrder',
                entityId: $transferOrderId,
                context: [
                    'correlation_id' => $correlationId,
                    'status' => $status,
                    'discrepancies' => $discrepancies,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return true;
        });
    }

    /**
     * Cancel transfer order
     *
     * @param  int  $transferOrderId  Transfer order ID
     * @param  string  $reason  Cancellation reason
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @return bool Success
     */
    public function cancelTransferOrder(
        int $transferOrderId,
        string $reason,
        int $userId,
        int $tenantId
    ): bool {
        $correlationId = Str::uuid()->toString();

        return $this->db->transaction(function () use (
            $transferOrderId,
            $reason,
            $userId,
            $tenantId,
            $correlationId
        ) {
            $transferOrder = $this->db->table('warehouse_transfer_orders')
                ->where('id', $transferOrderId)
                ->whereIn('status', ['pending', 'in_transit'])
                ->lockForUpdate()
                ->first();

            if (! $transferOrder) {
                throw new \RuntimeException("Cancellable transfer order {$transferOrderId} not found");
            }

            // Release all reservations
            $reservations = $this->db->table('stock_reservations')
                ->where('order_type', 'warehouse_transfer')
                ->where('order_id', $transferOrderId)
                ->where('status', 'active')
                ->get();

            foreach ($reservations as $reservation) {
                try {
                    $this->reservationService->releaseReservation(
                        $reservation->reservation_id,
                        $userId,
                        $tenantId
                    );
                } catch (\Exception $e) {
                    $this->logger->error('Failed to release reservation on transfer cancel', [
                        'reservation_id' => $reservation->reservation_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $this->db->table('warehouse_transfer_orders')
                ->where('id', $transferOrderId)
                ->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                    'cancelled_by' => $userId,
                    'cancellation_reason' => $reason,
                ]);

            $this->logAction(
                action: 'transfer_order_cancelled',
                entityType: 'WarehouseTransferOrder',
                entityId: $transferOrderId,
                context: [
                    'correlation_id' => $correlationId,
                    'reason' => $reason,
                    'reservations_released' => $reservations->count(),
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return true;
        });
    }

    /**
     * Get in-transit inventory for warehouse
     *
     * @param  int  $warehouseId  Warehouse ID
     * @return array In-transit items
     */
    public function getInTransitInventory(int $warehouseId): array
    {
        $incoming = $this->db->table('warehouse_transfer_orders as t')
            ->join('warehouse_transfer_items as i', 't.id', '=', 'i.transfer_order_id')
            ->where('t.to_warehouse_id', $warehouseId)
            ->where('t.status', 'in_transit')
            ->select('i.inventory_item_id', 'i.quantity')
            ->get()
            ->groupBy('inventory_item_id')
            ->map(fn ($items) => $items->sum('quantity'))
            ->toArray();

        $outgoing = $this->db->table('warehouse_transfer_orders as t')
            ->join('warehouse_transfer_items as i', 't.id', '=', 'i.transfer_order_id')
            ->where('t.from_warehouse_id', $warehouseId)
            ->where('t.status', 'in_transit')
            ->select('i.inventory_item_id', 'i.quantity')
            ->get()
            ->groupBy('inventory_item_id')
            ->map(fn ($items) => $items->sum('quantity'))
            ->toArray();

        return [
            'warehouse_id' => $warehouseId,
            'incoming' => $incoming,
            'outgoing' => $outgoing,
        ];
    }

    /**
     * Add stock to warehouse
     *
     * @param  int  $inventoryItemId  Inventory item ID
     * @param  int  $warehouseId  Warehouse ID
     * @param  int  $quantity  Quantity
     * @param  int  $tenantId  Tenant ID
     * @return bool Success
     */
    private function addStockToWarehouse(
        int $inventoryItemId,
        int $warehouseId,
        int $quantity,
        int $tenantId
    ): bool {
        $existingItem = $this->db->table('inventory_items')
            ->where('id', $inventoryItemId)
            ->where('warehouse_id', $warehouseId)
            ->first();

        if ($existingItem) {
            $this->db->table('inventory_items')
                ->where('id', $inventoryItemId)
                ->increment('current_stock', $quantity);
        } else {
            $sourceItem = $this->db->table('inventory_items')
                ->where('id', $inventoryItemId)
                ->first();

            if (! $sourceItem) {
                throw new \RuntimeException("Source inventory item {$inventoryItemId} not found");
            }

            $this->db->table('inventory_items')->insert([
                'product_id' => $sourceItem->product_id,
                'sku' => $sourceItem->sku,
                'name' => $sourceItem->name,
                'warehouse_id' => $warehouseId,
                'tenant_id' => $tenantId,
                'current_stock' => $quantity,
                'unit_cost' => $sourceItem->unit_cost,
                'created_at' => now(),
            ]);
        }

        return true;
    }

    /**
     * Generate transfer number
     *
     * @return string Transfer number
     */
    private function generateTransferNumber(): string
    {
        $prefix = 'TRF';
        $date = now()->format('Ymd');
        $sequence = $this->db->table('warehouse_transfer_orders')
            ->whereDate('created_at', today())
            ->count() + 1;

        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }
}
