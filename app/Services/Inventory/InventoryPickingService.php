<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Inventory Picking Service
 *
 * Manages warehouse picking operations:
 * - Generate pick lists
 * - Optimize pick routes
 * - Track picking progress
 * - Validate picked quantities
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class InventoryPickingService
{
    use WithAuditLogging;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Create pick list for order
     *
     * @param  int  $orderId  Order ID
     * @param  string  $orderType  Order type
     * @param  array<array<string, mixed>>  $items  Items to pick
     * @param  int  $warehouseId  Warehouse ID
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @return int Pick list ID
     */
    public function createPickList(
        int $orderId,
        string $orderType,
        array $items,
        int $warehouseId,
        int $userId,
        int $tenantId
    ): int {
        $correlationId = Str::uuid()->toString();

        return $this->db->transaction(function () use (
            $orderId,
            $orderType,
            $items,
            $warehouseId,
            $userId,
            $tenantId,
            $correlationId
        ) {
            $pickListId = $this->db->table('inventory_pick_lists')->insertGetId([
                'uuid' => Str::uuid()->toString(),
                'pick_list_number' => $this->generatePickListNumber(),
                'order_id' => $orderId,
                'order_type' => $orderType,
                'warehouse_id' => $warehouseId,
                'status' => 'pending',
                'priority' => $this->calculatePickPriority($orderType, $orderId),
                'tenant_id' => $tenantId,
                'created_by' => $userId,
                'created_at' => now(),
            ]);

            foreach ($items as $item) {
                $location = $this->getOptimalPickLocation($item['inventory_item_id'], $warehouseId);

                $this->db->table('inventory_pick_list_items')->insert([
                    'uuid' => Str::uuid()->toString(),
                    'pick_list_id' => $pickListId,
                    'inventory_item_id' => $item['inventory_item_id'],
                    'quantity_to_pick' => $item['quantity'],
                    'quantity_picked' => 0,
                    'location_id' => $location['location_id'] ?? null,
                    'location_code' => $location['location_code'] ?? null,
                    'zone_code' => $location['zone_code'] ?? null,
                    'pick_sequence' => $location['pick_sequence'] ?? 0,
                    'tenant_id' => $tenantId,
                    'created_at' => now(),
                ]);
            }

            $this->logCreated(
                entityType: 'InventoryPickList',
                entityId: $pickListId,
                context: [
                    'correlation_id' => $correlationId,
                    'pick_list_number' => $this->generatePickListNumber(),
                    'order_id' => $orderId,
                    'order_type' => $orderType,
                    'total_items' => count($items),
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return $pickListId;
        });
    }

    /**
     * Start picking
     *
     * @param  int  $pickListId  Pick list ID
     * @param  int  $pickerId  Picker user ID
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @return bool Success
     */
    public function startPicking(int $pickListId, int $pickerId, int $userId, int $tenantId): bool
    {
        $correlationId = Str::uuid()->toString();

        return $this->db->transaction(function () use (
            $pickListId,
            $pickerId,
            $userId,
            $tenantId,
            $correlationId
        ) {
            $this->db->table('inventory_pick_lists')
                ->where('id', $pickListId)
                ->where('status', 'pending')
                ->update([
                    'status' => 'in_progress',
                    'picker_id' => $pickerId,
                    'started_at' => now(),
                ]);

            $this->logAction(
                action: 'picking_started',
                entityType: 'InventoryPickList',
                entityId: $pickListId,
                context: [
                    'correlation_id' => $correlationId,
                    'picker_id' => $pickerId,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return true;
        });
    }

    /**
     * Record pick
     *
     * @param  int  $pickListItemId  Pick list item ID
     * @param  int  $quantityPicked  Quantity picked
     * @param  string|null  $batchNumber  Batch number (optional)
     * @param  int  $pickerId  Picker ID
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @return bool Success
     */
    public function recordPick(
        int $pickListItemId,
        int $quantityPicked,
        ?string $batchNumber,
        int $pickerId,
        int $userId,
        int $tenantId
    ): bool {
        $correlationId = Str::uuid()->toString();

        return $this->db->transaction(function () use (
            $pickListItemId,
            $quantityPicked,
            $batchNumber,
            $pickerId,
            $userId,
            $tenantId,
            $correlationId
        ) {
            $pickItem = $this->db->table('inventory_pick_list_items')
                ->where('id', $pickListItemId)
                ->lockForUpdate()
                ->first();

            if (! $pickItem) {
                throw new \RuntimeException("Pick list item {$pickListItemId} not found");
            }

            $newPickedQty = $pickItem->quantity_picked + $quantityPicked;

            if ($newPickedQty > $pickItem->quantity_to_pick) {
                throw new \RuntimeException("Picked quantity exceeds required quantity");
            }

            $this->db->table('inventory_pick_list_items')
                ->where('id', $pickListItemId)
                ->update([
                    'quantity_picked' => $newPickedQty,
                    'batch_number' => $batchNumber,
                    'last_picked_at' => now(),
                ]);

            $this->db->table('inventory_pick_logs')->insert([
                'uuid' => Str::uuid()->toString(),
                'pick_list_item_id' => $pickListItemId,
                'quantity' => $quantityPicked,
                'batch_number' => $batchNumber,
                'picker_id' => $pickerId,
                'tenant_id' => $tenantId,
                'created_at' => now(),
            ]);

            $this->logAction(
                action: 'pick_recorded',
                entityType: 'InventoryPickListItem',
                entityId: $pickListItemId,
                context: [
                    'correlation_id' => $correlationId,
                    'quantity' => $quantityPicked,
                    'batch_number' => $batchNumber,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return true;
        });
    }

    /**
     * Complete pick list
     *
     * @param  int  $pickListId  Pick list ID
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @return array Completion result
     */
    public function completePickList(int $pickListId, int $userId, int $tenantId): array
    {
        $correlationId = Str::uuid()->toString();

        return $this->db->transaction(function () use (
            $pickListId,
            $userId,
            $tenantId,
            $correlationId
        ) {
            $pickList = $this->db->table('inventory_pick_lists')
                ->where('id', $pickListId)
                ->where('status', 'in_progress')
                ->lockForUpdate()
                ->first();

            if (! $pickList) {
                throw new \RuntimeException("In-progress pick list {$pickListId} not found");
            }

            $items = $this->db->table('inventory_pick_list_items')
                ->where('pick_list_id', $pickListId)
                ->get();

            $shortages = [];

            foreach ($items as $item) {
                if ($item->quantity_picked < $item->quantity_to_pick) {
                    $shortages[] = [
                        'inventory_item_id' => $item->inventory_item_id,
                        'required' => $item->quantity_to_pick,
                        'picked' => $item->quantity_picked,
                        'shortage' => $item->quantity_to_pick - $item->quantity_picked,
                    ];
                }
            }

            $status = empty($shortages) ? 'completed' : 'completed_with_shortages';

            $this->db->table('inventory_pick_lists')
                ->where('id', $pickListId)
                ->update([
                    'status' => $status,
                    'completed_at' => now(),
                    'completed_by' => $userId,
                    'shortages' => ! empty($shortages) ? json_encode($shortages) : null,
                ]);

            $this->logAction(
                action: 'pick_list_completed',
                entityType: 'InventoryPickList',
                entityId: $pickListId,
                context: [
                    'correlation_id' => $correlationId,
                    'status' => $status,
                    'shortages' => $shortages,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return [
                'pick_list_id' => $pickListId,
                'status' => $status,
                'total_items' => $items->count(),
                'shortages' => $shortages,
            ];
        });
    }

    /**
     * Get optimal pick location
     *
     * @param  int  $inventoryItemId  Inventory item ID
     * @param  int  $warehouseId  Warehouse ID
     * @return array Location data
     */
    private function getOptimalPickLocation(int $inventoryItemId, int $warehouseId): array
    {
        $location = $this->db->table('location_items as li')
            ->join('warehouse_locations as l', 'li.location_id', '=', 'l.id')
            ->join('warehouse_zones as z', 'l.zone_id', '=', 'z.id')
            ->where('li.inventory_item_id', $inventoryItemId)
            ->where('l.warehouse_id', $warehouseId)
            ->where('li.quantity', '>', 0)
            ->where('l.is_active', true)
            ->orderByRaw('FIELD(z.type, "picking", "storage", "receiving") ASC')
            ->orderBy('l.pick_sequence', 'asc')
            ->select('l.id as location_id', 'l.code as location_code', 'z.code as zone_code', 'l.pick_sequence')
            ->first();

        return $location ? [
            'location_id' => $location->location_id,
            'location_code' => $location->location_code,
            'zone_code' => $location->zone_code,
            'pick_sequence' => $location->pick_sequence,
        ] : [];
    }

    /**
     * Calculate pick priority
     *
     * @param  string  $orderType  Order type
     * @param  int  $orderId  Order ID
     * @return int Priority (1-10)
     */
    private function calculatePickPriority(string $orderType, int $orderId): int
    {
        return match ($orderType) {
            'emergency', 'rush' => 10,
            'priority' => 8,
            'standard' => 5,
            'backorder' => 3,
            default => 5,
        };
    }

    /**
     * Generate pick list number
     *
     * @return string Pick list number
     */
    private function generatePickListNumber(): string
    {
        $prefix = 'PICK';
        $date = now()->format('Ymd');
        $sequence = $this->db->table('inventory_pick_lists')
            ->whereDate('created_at', today())
            ->count() + 1;

        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }

    /**
     * Get pick list summary
     *
     * @param  int  $pickListId  Pick list ID
     * @return array Summary
     */
    public function getPickListSummary(int $pickListId): array
    {
        $pickList = $this->db->table('inventory_pick_lists')
            ->where('id', $pickListId)
            ->first();

        if (! $pickList) {
            throw new \RuntimeException("Pick list {$pickListId} not found");
        }

        $items = $this->db->table('inventory_pick_list_items')
            ->where('pick_list_id', $pickListId)
            ->get();

        return [
            'pick_list_id' => $pickListId,
            'pick_list_number' => $pickList->pick_list_number,
            'order_id' => $pickList->order_id,
            'order_type' => $pickList->order_type,
            'warehouse_id' => $pickList->warehouse_id,
            'status' => $pickList->status,
            'priority' => $pickList->priority,
            'picker_id' => $pickList->picker_id,
            'created_at' => $pickList->created_at->toIso8601String(),
            'started_at' => $pickList->started_at?->toIso8601String(),
            'completed_at' => $pickList->completed_at?->toIso8601String(),
            'summary' => [
                'total_items' => $items->count(),
                'total_quantity_to_pick' => $items->sum('quantity_to_pick'),
                'total_quantity_picked' => $items->sum('quantity_picked'),
                'completion_percentage' => $items->sum('quantity_to_pick') > 0
                    ? round(($items->sum('quantity_picked') / $items->sum('quantity_to_pick')) * 100, 2)
                    : 0,
            ],
            'items' => $items->map(fn ($i) => [
                'inventory_item_id' => $i->inventory_item_id,
                'quantity_to_pick' => $i->quantity_to_pick,
                'quantity_picked' => $i->quantity_picked,
                'location_code' => $i->location_code,
                'zone_code' => $i->zone_code,
                'pick_sequence' => $i->pick_sequence,
            ])->toArray(),
        ];
    }
}
