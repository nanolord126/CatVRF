<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Inventory Cross-Dock Service
 *
 * Manages cross-docking operations:
 * - Direct transfer from receiving to shipping
 * - Bypass storage for fast-moving items
 * - Optimize cross-dock decisions
 * - Track cross-dock efficiency
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class InventoryCrossDockService
{
    use WithAuditLogging;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Create cross-dock transfer
     *
     * @param  int  $receiptId  Receipt ID
     * @param  int  $orderId  Target order ID
     * @param  array<array<string, mixed>>  $items  Items to cross-dock
     * @param  int  $warehouseId  Warehouse ID
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @return int Cross-dock ID
     */
    public function createCrossDockTransfer(
        int $receiptId,
        int $orderId,
        array $items,
        int $warehouseId,
        int $userId,
        int $tenantId
    ): int {
        $correlationId = Str::uuid()->toString();

        return $this->db->transaction(function () use (
            $receiptId,
            $orderId,
            $items,
            $warehouseId,
            $userId,
            $tenantId,
            $correlationId
        ) {
            $crossDockId = $this->db->table('inventory_cross_docks')->insertGetId([
                'uuid' => Str::uuid()->toString(),
                'cross_dock_number' => $this->generateCrossDockNumber(),
                'receipt_id' => $receiptId,
                'order_id' => $orderId,
                'warehouse_id' => $warehouseId,
                'status' => 'pending',
                'tenant_id' => $tenantId,
                'created_by' => $userId,
                'created_at' => now(),
            ]);

            foreach ($items as $item) {
                $this->db->table('inventory_cross_dock_items')->insert([
                    'uuid' => Str::uuid()->toString(),
                    'cross_dock_id' => $crossDockId,
                    'inventory_item_id' => $item['inventory_item_id'],
                    'quantity' => $item['quantity'],
                    'source_batch_number' => $item['batch_number'] ?? null,
                    'tenant_id' => $tenantId,
                    'created_at' => now(),
                ]);
            }

            $this->logCreated(
                entityType: 'InventoryCrossDock',
                entityId: $crossDockId,
                context: [
                    'correlation_id' => $correlationId,
                    'cross_dock_number' => $this->generateCrossDockNumber(),
                    'receipt_id' => $receiptId,
                    'order_id' => $orderId,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return $crossDockId;
        });
    }

    /**
     * Process cross-dock transfer
     *
     * @param  int  $crossDockId  Cross-dock ID
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @return array Processing result
     */
    public function processCrossDockTransfer(int $crossDockId, int $userId, int $tenantId): array
    {
        $correlationId = Str::uuid()->toString();

        return $this->db->transaction(function () use (
            $crossDockId,
            $userId,
            $tenantId,
            $correlationId
        ) {
            $crossDock = $this->db->table('inventory_cross_docks')
                ->where('id', $crossDockId)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->first();

            if (! $crossDock) {
                throw new \RuntimeException("Pending cross-dock {$crossDockId} not found");
            }

            $items = $this->db->table('inventory_cross_dock_items')
                ->where('cross_dock_id', $crossDockId)
                ->get();

            $processedItems = [];

            foreach ($items as $item) {
                $this->db->table('stock_movements')->insert([
                    'uuid' => Str::uuid()->toString(),
                    'correlation_id' => $correlationId,
                    'inventory_item_id' => $item->inventory_item_id,
                    'type' => 'cross_dock',
                    'quantity' => $item->quantity,
                    'reason' => 'Cross-dock transfer',
                    'source_type' => 'cross_dock',
                    'source_id' => $crossDockId,
                    'created_by' => $userId,
                    'created_at' => now(),
                ]);

                $processedItems[] = [
                    'inventory_item_id' => $item->inventory_item_id,
                    'quantity' => $item->quantity,
                ];
            }

            $this->db->table('inventory_cross_docks')
                ->where('id', $crossDockId)
                ->update([
                    'status' => 'completed',
                    'processed_at' => now(),
                    'processed_by' => $userId,
                ]);

            $this->logAction(
                action: 'cross_dock_transfer_processed',
                entityType: 'InventoryCrossDock',
                entityId: $crossDockId,
                context: [
                    'correlation_id' => $correlationId,
                    'processed_items' => $processedItems,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return [
                'cross_dock_id' => $crossDockId,
                'total_items' => count($processedItems),
                'processed_items' => $processedItems,
            ];
        });
    }

    /**
     * Evaluate if item should be cross-docked
     *
     * @param  int  $inventoryItemId  Inventory item ID
     * @param  int  $warehouseId  Warehouse ID
     * @return array Evaluation result
     */
    public function evaluateCrossDockEligibility(int $inventoryItemId, int $warehouseId): array
    {
        $item = $this->db->table('inventory_items')
            ->where('id', $inventoryItemId)
            ->where('warehouse_id', $warehouseId)
            ->first();

        if (! $item) {
            return [
                'eligible' => false,
                'reason' => 'Item not found',
            ];
        }

        $criteria = [];
        $score = 0;

        if ($item->abc_class === 'A') {
            $criteria[] = 'high_turnover';
            $score += 3;
        }

        $dailyUsage = $this->getAverageDailyUsage($inventoryItemId, 30);
        if ($dailyUsage > 10) {
            $criteria[] = 'high_demand';
            $score += 2;
        }

        $pendingOrders = $this->getPendingOrderQuantity($inventoryItemId);
        if ($pendingOrders > 0) {
            $criteria[] = 'has_pending_orders';
            $score += 2;
        }

        $item->cross_dock_eligible = $score >= 4;

        return [
            'inventory_item_id' => $inventoryItemId,
            'eligible' => $item->cross_dock_eligible,
            'score' => $score,
            'criteria_met' => $criteria,
            'daily_usage' => $dailyUsage,
            'pending_orders' => $pendingOrders,
        ];
    }

    /**
     * Get cross-dock efficiency metrics
     *
     * @param  int  $warehouseId  Warehouse ID
     * @param  int  $days  Number of days (default: 30)
     * @return array Efficiency metrics
     */
    public function getCrossDockEfficiency(int $warehouseId, int $days = 30): array
    {
        $startDate = now()->subDays($days)->toDateString();

        $crossDocks = $this->db->table('inventory_cross_docks')
            ->where('warehouse_id', $warehouseId)
            ->where('status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->get();

        if ($crossDocks->isEmpty()) {
            return [
                'warehouse_id' => $warehouseId,
                'period_days' => $days,
                'total_cross_docks' => 0,
                'avg_processing_time_minutes' => 0,
                'total_items_cross_docked' => 0,
                'storage_savings_percentage' => 0,
            ];
        }

        $avgProcessingTime = $crossDocks->avg(function ($cd) {
            if ($cd->created_at && $cd->processed_at) {
                return $cd->created_at->diffInMinutes($cd->processed_at);
            }
            return 0;
        });

        $totalItems = $this->db->table('inventory_cross_dock_items as cdi')
            ->join('inventory_cross_docks as cd', 'cdi.cross_dock_id', '=', 'cd.id')
            ->where('cd.warehouse_id', $warehouseId)
            ->where('cd.status', 'completed')
            ->where('cd.created_at', '>=', $startDate)
            ->sum('cdi.quantity');

        $totalReceipts = $this->db->table('inventory_receipts')
            ->where('warehouse_id', $warehouseId)
            ->where('created_at', '>=', $startDate)
            ->count();

        $storageSavings = $totalReceipts > 0 ? ($crossDocks->count() / $totalReceipts) * 100 : 0;

        return [
            'warehouse_id' => $warehouseId,
            'period_days' => $days,
            'total_cross_docks' => $crossDocks->count(),
            'avg_processing_time_minutes' => round($avgProcessingTime, 2),
            'total_items_cross_docked' => $totalItems,
            'storage_savings_percentage' => round($storageSavings, 2),
            'calculated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Get average daily usage
     *
     * @param  int  $inventoryItemId  Inventory item ID
     * @param  int  $days  Number of days
     * @return float Average daily usage
     */
    private function getAverageDailyUsage(int $inventoryItemId, int $days): float
    {
        $movements = $this->db->table('stock_movements')
            ->where('inventory_item_id', $inventoryItemId)
            ->where('type', 'out')
            ->where('created_at', '>=', now()->subDays($days))
            ->selectRaw('DATE(created_at) as date, SUM(ABS(quantity)) as usage')
            ->groupBy('date')
            ->get();

        if ($movements->isEmpty()) {
            return 0.0;
        }

        return $movements->sum('usage') / $days;
    }

    /**
     * Get pending order quantity
     *
     * @param  int  $inventoryItemId  Inventory item ID
     * @return int Pending quantity
     */
    private function getPendingOrderQuantity(int $inventoryItemId): int
    {
        return $this->db->table('inventory_allocations')
            ->where('inventory_item_id', $inventoryItemId)
            ->whereIn('status', ['allocated', 'partial'])
            ->sum('quantity');
    }

    /**
     * Generate cross-dock number
     *
     * @return string Cross-dock number
     */
    private function generateCrossDockNumber(): string
    {
        $prefix = 'CD';
        $date = now()->format('Ymd');
        $sequence = $this->db->table('inventory_cross_docks')
            ->whereDate('created_at', today())
            ->count() + 1;

        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }
}
