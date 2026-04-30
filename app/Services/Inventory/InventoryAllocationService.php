<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Inventory Allocation Service
 *
 * Manages inventory allocation for orders:
 * - Allocate stock to orders
 * - Deallocation on order cancellation
 * - Backorder management
 * - Allocation priority rules
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class InventoryAllocationService
{
    use WithAuditLogging;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService,
        private readonly StockReservationService $reservationService,
    ) {}

    /**
     * Allocate inventory to order
     *
     * @param  int  $orderId  Order ID
     * @param  string  $orderType  Order type
     * @param  array<array<string, mixed>>  $items  Items to allocate
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @return array Allocation result
     */
    public function allocateToOrder(
        int $orderId,
        string $orderType,
        array $items,
        int $userId,
        int $tenantId
    ): array {
        $correlationId = Str::uuid()->toString();

        return $this->db->transaction(function () use (
            $orderId,
            $orderType,
            $items,
            $userId,
            $tenantId,
            $correlationId
        ) {
            $allocations = [];
            $backorders = [];
            $successfulCount = 0;
            $backorderCount = 0;

            foreach ($items as $item) {
                $inventoryItemId = $item['inventory_item_id'];
                $quantity = $item['quantity'];
                $warehouseId = $item['warehouse_id'] ?? null;

                $availableStock = $this->getAvailableStock($inventoryItemId, $warehouseId);

                if ($availableStock >= $quantity) {
                    $reservationId = $this->reservationService->reserveStock(
                        $inventoryItemId,
                        $quantity,
                        $orderType,
                        $orderId,
                        $userId,
                        $tenantId,
                        7200 // 2 hours TTL for allocations
                    );

                    $allocations[] = [
                        'inventory_item_id' => $inventoryItemId,
                        'quantity' => $quantity,
                        'reservation_id' => $reservationId,
                        'status' => 'allocated',
                    ];

                    $this->db->table('inventory_allocations')->insert([
                        'uuid' => Str::uuid()->toString(),
                        'order_id' => $orderId,
                        'order_type' => $orderType,
                        'inventory_item_id' => $inventoryItemId,
                        'quantity' => $quantity,
                        'reservation_id' => $reservationId,
                        'status' => 'allocated',
                        'warehouse_id' => $warehouseId,
                        'tenant_id' => $tenantId,
                        'allocated_by' => $userId,
                        'allocated_at' => now(),
                    ]);

                    $successfulCount++;
                } else {
                    $backorderQty = $quantity - $availableStock;

                    if ($availableStock > 0) {
                        $reservationId = $this->reservationService->reserveStock(
                            $inventoryItemId,
                            $availableStock,
                            $orderType,
                            $orderId,
                            $userId,
                            $tenantId,
                            7200
                        );

                        $allocations[] = [
                            'inventory_item_id' => $inventoryItemId,
                            'quantity' => $availableStock,
                            'reservation_id' => $reservationId,
                            'status' => 'partial',
                        ];

                        $this->db->table('inventory_allocations')->insert([
                            'uuid' => Str::uuid()->toString(),
                            'order_id' => $orderId,
                            'order_type' => $orderType,
                            'inventory_item_id' => $inventoryItemId,
                            'quantity' => $availableStock,
                            'reservation_id' => $reservationId,
                            'status' => 'partial',
                            'warehouse_id' => $warehouseId,
                            'tenant_id' => $tenantId,
                            'allocated_by' => $userId,
                            'allocated_at' => now(),
                        ]);
                    }

                    $backorders[] = [
                        'inventory_item_id' => $inventoryItemId,
                        'requested_quantity' => $quantity,
                        'allocated_quantity' => $availableStock,
                        'backorder_quantity' => $backorderQty,
                    ];

                    $this->db->table('inventory_backorders')->insert([
                        'uuid' => Str::uuid()->toString(),
                        'order_id' => $orderId,
                        'order_type' => $orderType,
                        'inventory_item_id' => $inventoryItemId,
                        'requested_quantity' => $quantity,
                        'allocated_quantity' => $availableStock,
                        'backorder_quantity' => $backorderQty,
                        'warehouse_id' => $warehouseId,
                        'tenant_id' => $tenantId,
                        'created_at' => now(),
                    ]);

                    $backorderCount++;
                }
            }

            $this->logAction(
                action: 'inventory_allocated_to_order',
                entityType: 'InventoryAllocation',
                entityId: $orderId,
                context: [
                    'correlation_id' => $correlationId,
                    'order_type' => $orderType,
                    'total_items' => count($items),
                    'successful_count' => $successfulCount,
                    'backorder_count' => $backorderCount,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return [
                'correlation_id' => $correlationId,
                'order_id' => $orderId,
                'total_items' => count($items),
                'successful_count' => $successfulCount,
                'backorder_count' => $backorderCount,
                'allocations' => $allocations,
                'backorders' => $backorders,
            ];
        });
    }

    /**
     * Deallocate inventory from order
     *
     * @param  int  $orderId  Order ID
     * @param  string  $reason  Deallocation reason
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @return bool Success
     */
    public function deallocateFromOrder(
        int $orderId,
        string $reason,
        int $userId,
        int $tenantId
    ): bool {
        $correlationId = Str::uuid()->toString();

        return $this->db->transaction(function () use (
            $orderId,
            $reason,
            $userId,
            $tenantId,
            $correlationId
        ) {
            $allocations = $this->db->table('inventory_allocations')
                ->where('order_id', $orderId)
                ->where('status', '!=', 'deallocated')
                ->get();

            foreach ($allocations as $allocation) {
                if ($allocation->reservation_id) {
                    try {
                        $this->reservationService->releaseReservation(
                            $allocation->reservation_id,
                            $userId,
                            $tenantId
                        );
                    } catch (\Exception $e) {
                        $this->logger->error('Failed to release reservation on deallocation', [
                            'reservation_id' => $allocation->reservation_id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                $this->db->table('inventory_allocations')
                    ->where('id', $allocation->id)
                    ->update([
                        'status' => 'deallocated',
                        'deallocated_at' => now(),
                        'deallocated_by' => $userId,
                        'deallocation_reason' => $reason,
                    ]);
            }

            // Remove backorders
            $this->db->table('inventory_backorders')
                ->where('order_id', $orderId)
                ->delete();

            $this->logAction(
                action: 'inventory_deallocated_from_order',
                entityType: 'InventoryAllocation',
                entityId: $orderId,
                context: [
                    'correlation_id' => $correlationId,
                    'reason' => $reason,
                    'allocations_released' => $allocations->count(),
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return true;
        });
    }

    /**
     * Process backorders when stock becomes available
     *
     * @param  int  $inventoryItemId  Inventory item ID
     * @param  int  $availableQuantity  Available quantity
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @return array Processing result
     */
    public function processBackorders(
        int $inventoryItemId,
        int $availableQuantity,
        int $userId,
        int $tenantId
    ): array {
        $correlationId = Str::uuid()->toString();

        return $this->db->transaction(function () use (
            $inventoryItemId,
            $availableQuantity,
            $userId,
            $tenantId,
            $correlationId
        ) {
            $backorders = $this->db->table('inventory_backorders')
                ->where('inventory_item_id', $inventoryItemId)
                ->where('backorder_quantity', '>', 0)
                ->orderBy('created_at', 'asc')
                ->get();

            $processed = [];
            $remainingQty = $availableQuantity;

            foreach ($backorders as $backorder) {
                if ($remainingQty <= 0) {
                    break;
                }

                $qtyToAllocate = min($backorder->backorder_quantity, $remainingQty);

                $reservationId = $this->reservationService->reserveStock(
                    $inventoryItemId,
                    $qtyToAllocate,
                    $backorder->order_type,
                    $backorder->order_id,
                    $userId,
                    $tenantId,
                    7200
                );

                $this->db->table('inventory_allocations')->insert([
                    'uuid' => Str::uuid()->toString(),
                    'order_id' => $backorder->order_id,
                    'order_type' => $backorder->order_type,
                    'inventory_item_id' => $inventoryItemId,
                    'quantity' => $qtyToAllocate,
                    'reservation_id' => $reservationId,
                    'status' => 'allocated',
                    'warehouse_id' => $backorder->warehouse_id,
                    'tenant_id' => $tenantId,
                    'allocated_by' => $userId,
                    'allocated_at' => now(),
                ]);

                $this->db->table('inventory_backorders')
                    ->where('id', $backorder->id)
                    ->decrement('backorder_quantity', $qtyToAllocate);

                if ($backorder->backorder_quantity - $qtyToAllocate <= 0) {
                    $this->db->table('inventory_backorders')
                        ->where('id', $backorder->id)
                        ->update(['status' => 'fulfilled']);
                }

                $processed[] = [
                    'order_id' => $backorder->order_id,
                    'quantity' => $qtyToAllocate,
                    'reservation_id' => $reservationId,
                ];

                $remainingQty -= $qtyToAllocate;
            }

            $this->logAction(
                action: 'backorders_processed',
                entityType: 'InventoryBackorder',
                entityId: $inventoryItemId,
                context: [
                    'correlation_id' => $correlationId,
                    'available_quantity' => $availableQuantity,
                    'processed_count' => count($processed),
                    'remaining_quantity' => $remainingQty,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return [
                'correlation_id' => $correlationId,
                'inventory_item_id' => $inventoryItemId,
                'processed_count' => count($processed),
                'processed' => $processed,
                'remaining_quantity' => $remainingQty,
            ];
        });
    }

    /**
     * Get available stock for allocation
     *
     * @param  int  $inventoryItemId  Inventory item ID
     * @param  int|null  $warehouseId  Warehouse ID (optional)
     * @return int Available quantity
     */
    private function getAvailableStock(int $inventoryItemId, ?int $warehouseId = null): int
    {
        $query = $this->db->table('inventory_items')
            ->where('id', $inventoryItemId);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        $item = $query->first();

        if (! $item) {
            return 0;
        }

        $reservedQty = $this->reservationService->getTotalReservedQuantity($inventoryItemId);

        return max(0, $item->current_stock - $item->hold_stock - $reservedQty);
    }

    /**
     * Get allocation summary for order
     *
     * @param  int  $orderId  Order ID
     * @return array Allocation summary
     */
    public function getAllocationSummary(int $orderId): array
    {
        $allocations = $this->db->table('inventory_allocations')
            ->where('order_id', $orderId)
            ->get();

        $backorders = $this->db->table('inventory_backorders')
            ->where('order_id', $orderId)
            ->get();

        return [
            'order_id' => $orderId,
            'total_allocations' => $allocations->count(),
            'allocated_quantity' => $allocations->sum('quantity'),
            'backorder_count' => $backorders->count(),
            'backorder_quantity' => $backorders->sum('backorder_quantity'),
            'allocations' => $allocations->map(fn ($a) => [
                'inventory_item_id' => $a->inventory_item_id,
                'quantity' => $a->quantity,
                'status' => $a->status,
                'allocated_at' => $a->allocated_at->toIso8601String(),
            ])->toArray(),
            'backorders' => $backorders->map(fn ($b) => [
                'inventory_item_id' => $b->inventory_item_id,
                'requested_quantity' => $b->requested_quantity,
                'backorder_quantity' => $b->backorder_quantity,
                'status' => $b->status,
            ])->toArray(),
        ];
    }
}
