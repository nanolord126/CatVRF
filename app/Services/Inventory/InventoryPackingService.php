<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Inventory Packing Service
 *
 * Manages warehouse packing operations:
 * - Create pack lists
 * - Validate packing
 * - Generate shipping labels
 * - Track packing efficiency
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class InventoryPackingService
{
    use WithAuditLogging;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Create pack list from pick list
     *
     * @param  int  $pickListId  Pick list ID
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @return int Pack list ID
     */
    public function createPackList(int $pickListId, int $userId, int $tenantId): int
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
                ->where('status', 'completed')
                ->first();

            if (! $pickList) {
                throw new \RuntimeException("Completed pick list {$pickListId} not found");
            }

            $packListId = $this->db->table('inventory_pack_lists')->insertGetId([
                'uuid' => Str::uuid()->toString(),
                'pack_list_number' => $this->generatePackListNumber(),
                'pick_list_id' => $pickListId,
                'order_id' => $pickList->order_id,
                'order_type' => $pickList->order_type,
                'warehouse_id' => $pickList->warehouse_id,
                'status' => 'pending',
                'tenant_id' => $tenantId,
                'created_by' => $userId,
                'created_at' => now(),
            ]);

            $pickItems = $this->db->table('inventory_pick_list_items')
                ->where('pick_list_id', $pickListId)
                ->get();

            foreach ($pickItems as $item) {
                $this->db->table('inventory_pack_list_items')->insert([
                    'uuid' => Str::uuid()->toString(),
                    'pack_list_id' => $packListId,
                    'inventory_item_id' => $item->inventory_item_id,
                    'quantity_to_pack' => $item->quantity_picked,
                    'quantity_packed' => 0,
                    'tenant_id' => $tenantId,
                    'created_at' => now(),
                ]);
            }

            $this->logCreated(
                entityType: 'InventoryPackList',
                entityId: $packListId,
                context: [
                    'correlation_id' => $correlationId,
                    'pack_list_number' => $this->generatePackListNumber(),
                    'pick_list_id' => $pickListId,
                    'order_id' => $pickList->order_id,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return $packListId;
        });
    }

    /**
     * Start packing
     *
     * @param  int  $packListId  Pack list ID
     * @param  int  $packerId  Packer user ID
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @return bool Success
     */
    public function startPacking(int $packListId, int $packerId, int $userId, int $tenantId): bool
    {
        $correlationId = Str::uuid()->toString();

        return $this->db->transaction(function () use (
            $packListId,
            $packerId,
            $userId,
            $tenantId,
            $correlationId
        ) {
            $this->db->table('inventory_pack_lists')
                ->where('id', $packListId)
                ->where('status', 'pending')
                ->update([
                    'status' => 'in_progress',
                    'packer_id' => $packerId,
                    'started_at' => now(),
                ]);

            $this->logAction(
                action: 'packing_started',
                entityType: 'InventoryPackList',
                entityId: $packListId,
                context: [
                    'correlation_id' => $correlationId,
                    'packer_id' => $packerId,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return true;
        });
    }

    /**
     * Record pack
     *
     * @param  int  $packListItemId  Pack list item ID
     * @param  int  $quantityPacked  Quantity packed
     * @param  string|null  $containerNumber  Container number
     * @param  int  $packerId  Packer ID
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @return bool Success
     */
    public function recordPack(
        int $packListItemId,
        int $quantityPacked,
        ?string $containerNumber,
        int $packerId,
        int $userId,
        int $tenantId
    ): bool {
        $correlationId = Str::uuid()->toString();

        return $this->db->transaction(function () use (
            $packListItemId,
            $quantityPacked,
            $containerNumber,
            $packerId,
            $userId,
            $tenantId,
            $correlationId
        ) {
            $packItem = $this->db->table('inventory_pack_list_items')
                ->where('id', $packListItemId)
                ->lockForUpdate()
                ->first();

            if (! $packItem) {
                throw new \RuntimeException("Pack list item {$packListItemId} not found");
            }

            $newPackedQty = $packItem->quantity_packed + $quantityPacked;

            if ($newPackedQty > $packItem->quantity_to_pack) {
                throw new \RuntimeException("Packed quantity exceeds required quantity");
            }

            $this->db->table('inventory_pack_list_items')
                ->where('id', $packListItemId)
                ->update([
                    'quantity_packed' => $newPackedQty,
                    'container_number' => $containerNumber,
                    'last_packed_at' => now(),
                ]);

            $this->db->table('inventory_pack_logs')->insert([
                'uuid' => Str::uuid()->toString(),
                'pack_list_item_id' => $packListItemId,
                'quantity' => $quantityPacked,
                'container_number' => $containerNumber,
                'packer_id' => $packerId,
                'tenant_id' => $tenantId,
                'created_at' => now(),
            ]);

            $this->logAction(
                action: 'pack_recorded',
                entityType: 'InventoryPackListItem',
                entityId: $packListItemId,
                context: [
                    'correlation_id' => $correlationId,
                    'quantity' => $quantityPacked,
                    'container_number' => $containerNumber,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return true;
        });
    }

    /**
     * Complete pack list
     *
     * @param  int  $packListId  Pack list ID
     * @param  string  $shippingCarrier  Shipping carrier
     * @param  string  $trackingNumber  Tracking number
     * @param  float  $packageWeight  Package weight
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @return array Completion result
     */
    public function completePackList(
        int $packListId,
        string $shippingCarrier,
        string $trackingNumber,
        float $packageWeight,
        int $userId,
        int $tenantId
    ): array {
        $correlationId = Str::uuid()->toString();

        return $this->db->transaction(function () use (
            $packListId,
            $shippingCarrier,
            $trackingNumber,
            $packageWeight,
            $userId,
            $tenantId,
            $correlationId
        ) {
            $packList = $this->db->table('inventory_pack_lists')
                ->where('id', $packListId)
                ->where('status', 'in_progress')
                ->lockForUpdate()
                ->first();

            if (! $packList) {
                throw new \RuntimeException("In-progress pack list {$packListId} not found");
            }

            $items = $this->db->table('inventory_pack_list_items')
                ->where('pack_list_id', $packListId)
                ->get();

            $discrepancies = [];

            foreach ($items as $item) {
                if ($item->quantity_packed < $item->quantity_to_pack) {
                    $discrepancies[] = [
                        'inventory_item_id' => $item->inventory_item_id,
                        'required' => $item->quantity_to_pack,
                        'packed' => $item->quantity_packed,
                        'shortage' => $item->quantity_to_pack - $item->quantity_packed,
                    ];
                }
            }

            $status = empty($discrepancies) ? 'completed' : 'completed_with_discrepancies';

            $this->db->table('inventory_pack_lists')
                ->where('id', $packListId)
                ->update([
                    'status' => $status,
                    'shipping_carrier' => $shippingCarrier,
                    'tracking_number' => $trackingNumber,
                    'package_weight' => $packageWeight,
                    'completed_at' => now(),
                    'completed_by' => $userId,
                    'discrepancies' => ! empty($discrepancies) ? json_encode($discrepancies) : null,
                ]);

            $this->logAction(
                action: 'pack_list_completed',
                entityType: 'InventoryPackList',
                entityId: $packListId,
                context: [
                    'correlation_id' => $correlationId,
                    'status' => $status,
                    'shipping_carrier' => $shippingCarrier,
                    'tracking_number' => $trackingNumber,
                    'discrepancies' => $discrepancies,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return [
                'pack_list_id' => $packListId,
                'status' => $status,
                'shipping_carrier' => $shippingCarrier,
                'tracking_number' => $trackingNumber,
                'package_weight' => $packageWeight,
                'total_items' => $items->count(),
                'discrepancies' => $discrepancies,
            ];
        });
    }

    /**
     * Generate pack list number
     *
     * @return string Pack list number
     */
    private function generatePackListNumber(): string
    {
        $prefix = 'PACK';
        $date = now()->format('Ymd');
        $sequence = $this->db->table('inventory_pack_lists')
            ->whereDate('created_at', today())
            ->count() + 1;

        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }

    /**
     * Get pack list summary
     *
     * @param  int  $packListId  Pack list ID
     * @return array Summary
     */
    public function getPackListSummary(int $packListId): array
    {
        $packList = $this->db->table('inventory_pack_lists')
            ->where('id', $packListId)
            ->first();

        if (! $packList) {
            throw new \RuntimeException("Pack list {$packListId} not found");
        }

        $items = $this->db->table('inventory_pack_list_items')
            ->where('pack_list_id', $packListId)
            ->get();

        return [
            'pack_list_id' => $packListId,
            'pack_list_number' => $packList->pack_list_number,
            'pick_list_id' => $packList->pick_list_id,
            'order_id' => $packList->order_id,
            'order_type' => $packList->order_type,
            'warehouse_id' => $packList->warehouse_id,
            'status' => $packList->status,
            'packer_id' => $packList->packer_id,
            'shipping_carrier' => $packList->shipping_carrier,
            'tracking_number' => $packList->tracking_number,
            'package_weight' => $packList->package_weight,
            'created_at' => $packList->created_at->toIso8601String(),
            'started_at' => $packList->started_at?->toIso8601String(),
            'completed_at' => $packList->completed_at?->toIso8601String(),
            'summary' => [
                'total_items' => $items->count(),
                'total_quantity_to_pack' => $items->sum('quantity_to_pack'),
                'total_quantity_packed' => $items->sum('quantity_packed'),
                'completion_percentage' => $items->sum('quantity_to_pack') > 0
                    ? round(($items->sum('quantity_packed') / $items->sum('quantity_to_pack')) * 100, 2)
                    : 0,
            ],
            'items' => $items->map(fn ($i) => [
                'inventory_item_id' => $i->inventory_item_id,
                'quantity_to_pack' => $i->quantity_to_pack,
                'quantity_packed' => $i->quantity_packed,
                'container_number' => $i->container_number,
            ])->toArray(),
        ];
    }

    /**
     * Get packing efficiency metrics
     *
     * @param  int  $warehouseId  Warehouse ID
     * @param  int  $days  Number of days (default: 30)
     * @return array Efficiency metrics
     */
    public function getPackingEfficiency(int $warehouseId, int $days = 30): array
    {
        $startDate = now()->subDays($days)->toDateString();

        $packLists = $this->db->table('inventory_pack_lists')
            ->where('warehouse_id', $warehouseId)
            ->where('status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->get();

        if ($packLists->isEmpty()) {
            return [
                'warehouse_id' => $warehouseId,
                'period_days' => $days,
                'total_pack_lists' => 0,
                'avg_packing_time_minutes' => 0,
                'avg_items_per_pack_list' => 0,
                'discrepancy_rate' => 0,
            ];
        }

        $avgPackingTime = $packLists->avg(function ($pl) {
            if ($pl->started_at && $pl->completed_at) {
                return $pl->started_at->diffInMinutes($pl->completed_at);
            }
            return 0;
        });

        $totalItems = $this->db->table('inventory_pack_list_items as pi')
            ->join('inventory_pack_lists as pl', 'pi.pack_list_id', '=', 'pl.id')
            ->where('pl.warehouse_id', $warehouseId)
            ->where('pl.status', 'completed')
            ->where('pl.created_at', '>=', $startDate)
            ->count();

        $discrepancyCount = $packLists->filter(fn ($pl) => $pl->discrepancies !== null)->count();
        $discrepancyRate = ($discrepancyCount / $packLists->count()) * 100;

        return [
            'warehouse_id' => $warehouseId,
            'period_days' => $days,
            'total_pack_lists' => $packLists->count(),
            'avg_packing_time_minutes' => round($avgPackingTime, 2),
            'avg_items_per_pack_list' => round($totalItems / $packLists->count(), 2),
            'discrepancy_rate' => round($discrepancyRate, 2),
            'calculated_at' => now()->toIso8601String(),
        ];
    }
}
