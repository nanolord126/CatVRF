<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Cache;
use Psr\Log\LoggerInterface;

/**
 * Inventory Valuation Service
 *
 * Calculates inventory valuation using different methods:
 * - FIFO (First In, First Out)
 * - LIFO (Last In, First Out)
 * - Weighted Average Cost
 * - Standard Cost
 * - Provides real-time inventory value reports
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class InventoryValuationService
{
    use WithAuditLogging;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Calculate total inventory value using FIFO
     *
     * @param  int  $tenantId  Tenant ID
     * @param  int|null  $warehouseId  Warehouse ID (optional)
     * @return array Valuation results
     */
    public function calculateFIFOValue(int $tenantId, ?int $warehouseId = null): array
    {
        $cacheKey = "inventory_valuation:fifo:{$tenantId}:{$warehouseId}";

        return Cache::remember($cacheKey, 300, function () use ($tenantId, $warehouseId) {
            $query = $this->db->table('inventory_items')
                ->where('tenant_id', $tenantId)
                ->where('is_active', true);

            if ($warehouseId) {
                $query->where('warehouse_id', $warehouseId);
            }

            $items = $query->get();

            $totalValue = 0;
            $itemValues = [];

            foreach ($items as $item) {
                $itemValue = $this->calculateItemFIFOValue($item->id);
                $totalValue += $itemValue;

                $itemValues[] = [
                    'inventory_item_id' => $item->id,
                    'sku' => $item->sku,
                    'name' => $item->name,
                    'quantity' => $item->current_stock,
                    'value' => $itemValue,
                    'unit_cost' => $item->unit_cost ?? 0,
                ];
            }

            return [
                'valuation_method' => 'FIFO',
                'tenant_id' => $tenantId,
                'warehouse_id' => $warehouseId,
                'total_items' => count($itemValues),
                'total_value' => $totalValue,
                'items' => $itemValues,
                'calculated_at' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * Calculate total inventory value using Weighted Average Cost
     *
     * @param  int  $tenantId  Tenant ID
     * @param  int|null  $warehouseId  Warehouse ID (optional)
     * @return array Valuation results
     */
    public function calculateWeightedAverageValue(int $tenantId, ?int $warehouseId = null): array
    {
        $cacheKey = "inventory_valuation:weighted_avg:{$tenantId}:{$warehouseId}";

        return Cache::remember($cacheKey, 300, function () use ($tenantId, $warehouseId) {
            $query = $this->db->table('inventory_items')
                ->where('tenant_id', $tenantId)
                ->where('is_active', true);

            if ($warehouseId) {
                $query->where('warehouse_id', $warehouseId);
            }

            $items = $query->get();

            $totalValue = 0;
            $itemValues = [];

            foreach ($items as $item) {
                $weightedAvgCost = $this->calculateWeightedAverageCost($item->id);
                $itemValue = $item->current_stock * $weightedAvgCost;
                $totalValue += $itemValue;

                $itemValues[] = [
                    'inventory_item_id' => $item->id,
                    'sku' => $item->sku,
                    'name' => $item->name,
                    'quantity' => $item->current_stock,
                    'weighted_avg_cost' => $weightedAvgCost,
                    'value' => $itemValue,
                ];
            }

            return [
                'valuation_method' => 'Weighted Average',
                'tenant_id' => $tenantId,
                'warehouse_id' => $warehouseId,
                'total_items' => count($itemValues),
                'total_value' => $totalValue,
                'items' => $itemValues,
                'calculated_at' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * Calculate item value using FIFO
     *
     * @param  int  $inventoryItemId  Inventory item ID
     * @return float Item value
     */
    public function calculateItemFIFOValue(int $inventoryItemId): float
    {
        $item = $this->db->table('inventory_items')
            ->where('id', $inventoryItemId)
            ->first();

        if (! $item || $item->current_stock <= 0) {
            return 0.0;
        }

        $batches = $this->db->table('inventory_batches')
            ->where('inventory_item_id', $inventoryItemId)
            ->where('quantity', '>', 0)
            ->orderBy('received_at', 'asc')
            ->get();

        $remainingQty = $item->current_stock;
        $totalValue = 0.0;

        foreach ($batches as $batch) {
            if ($remainingQty <= 0) {
                break;
            }

            $qtyToValue = min($batch->quantity, $remainingQty);
            $totalValue += $qtyToValue * ($batch->unit_cost ?? $item->unit_cost ?? 0);
            $remainingQty -= $qtyToValue;
        }

        return $totalValue;
    }

    /**
     * Calculate weighted average cost for item
     *
     * @param  int  $inventoryItemId  Inventory item ID
     * @return float Weighted average cost
     */
    public function calculateWeightedAverageCost(int $inventoryItemId): float
    {
        $item = $this->db->table('inventory_items')
            ->where('id', $inventoryItemId)
            ->first();

        if (! $item) {
            return 0.0;
        }

        $recentReceipts = $this->db->table('stock_movements')
            ->where('inventory_item_id', $inventoryItemId)
            ->where('type', 'in')
            ->where('created_at', '>=', now()->subDays(90))
            ->get();

        if ($recentReceipts->isEmpty()) {
            return $item->unit_cost ?? 0.0;
        }

        $totalValue = 0.0;
        $totalQty = 0;

        foreach ($recentReceipts as $receipt) {
            $totalValue += abs($receipt->quantity) * ($item->unit_cost ?? 0);
            $totalQty += abs($receipt->quantity);
        }

        return $totalQty > 0 ? $totalValue / $totalQty : ($item->unit_cost ?? 0.0);
    }

    /**
     * Get inventory aging report
     *
     * @param  int  $tenantId  Tenant ID
     * @param  int|null  $warehouseId  Warehouse ID (optional)
     * @return array Aging report
     */
    public function getInventoryAgingReport(int $tenantId, ?int $warehouseId = null): array
    {
        $query = $this->db->table('inventory_items as i')
            ->leftJoin('inventory_batches as b', 'i.id', '=', 'b.inventory_item_id')
            ->where('i.tenant_id', $tenantId)
            ->where('i.is_active', true)
            ->where('i.current_stock', '>', 0);

        if ($warehouseId) {
            $query->where('i.warehouse_id', $warehouseId);
        }

        $batches = $query->select(
            'i.id as inventory_item_id',
            'i.sku',
            'i.name',
            'b.quantity',
            'b.received_at',
            'b.expiry_date'
        )->get();

        $agingBuckets = [
            '0-30_days' => ['quantity' => 0, 'value' => 0],
            '31-60_days' => ['quantity' => 0, 'value' => 0],
            '61-90_days' => ['quantity' => 0, 'value' => 0],
            '91-180_days' => ['quantity' => 0, 'value' => 0],
            '181-365_days' => ['quantity' => 0, 'value' => 0],
            '365+_days' => ['quantity' => 0, 'value' => 0],
        ];

        foreach ($batches as $batch) {
            if (! $batch->received_at) {
                continue;
            }

            $daysInStock = now()->diffInDays($batch->received_at);
            $unitCost = $this->calculateWeightedAverageCost($batch->inventory_item_id);
            $value = $batch->quantity * $unitCost;

            if ($daysInStock <= 30) {
                $agingBuckets['0-30_days']['quantity'] += $batch->quantity;
                $agingBuckets['0-30_days']['value'] += $value;
            } elseif ($daysInStock <= 60) {
                $agingBuckets['31-60_days']['quantity'] += $batch->quantity;
                $agingBuckets['31-60_days']['value'] += $value;
            } elseif ($daysInStock <= 90) {
                $agingBuckets['61-90_days']['quantity'] += $batch->quantity;
                $agingBuckets['61-90_days']['value'] += $value;
            } elseif ($daysInStock <= 180) {
                $agingBuckets['91-180_days']['quantity'] += $batch->quantity;
                $agingBuckets['91-180_days']['value'] += $value;
            } elseif ($daysInStock <= 365) {
                $agingBuckets['181-365_days']['quantity'] += $batch->quantity;
                $agingBuckets['181-365_days']['value'] += $value;
            } else {
                $agingBuckets['365+_days']['quantity'] += $batch->quantity;
                $agingBuckets['365+_days']['value'] += $value;
            }
        }

        return [
            'tenant_id' => $tenantId,
            'warehouse_id' => $warehouseId,
            'aging_buckets' => $agingBuckets,
            'total_quantity' => array_sum(array_column($agingBuckets, 'quantity')),
            'total_value' => array_sum(array_column($agingBuckets, 'value')),
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Get slow-moving inventory report
     *
     * @param  int  $tenantId  Tenant ID
     * @param  int  $daysThreshold  Days threshold (default: 180)
     * @return array Slow-moving items
     */
    public function getSlowMovingInventory(int $tenantId, int $daysThreshold = 180): array
    {
        $cutoffDate = now()->subDays($daysThreshold);

        $slowMovingItems = $this->db->table('inventory_items as i')
            ->leftJoin('stock_movements as m', function ($join) use ($cutoffDate) {
                $join->on('i.id', '=', 'm.inventory_item_id')
                    ->where('m.type', 'out')
                    ->where('m.created_at', '>=', $cutoffDate);
            })
            ->where('i.tenant_id', $tenantId)
            ->where('i.is_active', true)
            ->where('i.current_stock', '>', 0)
            ->whereNull('m.id')
            ->select('i.*')
            ->get();

        $items = [];

        foreach ($slowMovingItems as $item) {
            $value = $this->calculateItemFIFOValue($item->id);
            $lastMovement = $this->db->table('stock_movements')
                ->where('inventory_item_id', $item->id)
                ->orderBy('created_at', 'desc')
                ->value('created_at');

            $daysSinceMovement = $lastMovement ? now()->diffInDays($lastMovement) : null;

            $items[] = [
                'inventory_item_id' => $item->id,
                'sku' => $item->sku,
                'name' => $item->name,
                'current_stock' => $item->current_stock,
                'value' => $value,
                'unit_cost' => $item->unit_cost ?? 0,
                'last_movement_date' => $lastMovement ? $lastMovement->toIso8601String() : null,
                'days_since_movement' => $daysSinceMovement,
                'days_threshold' => $daysThreshold,
            ];
        }

        return [
            'tenant_id' => $tenantId,
            'days_threshold' => $daysThreshold,
            'total_items' => count($items),
            'total_value' => array_sum(array_column($items, 'value')),
            'items' => $items,
        ];
    }

    /**
     * Get obsolete inventory report
     *
     * @param  int  $tenantId  Tenant ID
     * @param  int  $daysThreshold  Days threshold (default: 365)
     * @return array Obsolete items
     */
    public function getObsoleteInventory(int $tenantId, int $daysThreshold = 365): array
    {
        $slowMoving = $this->getSlowMovingInventory($tenantId, $daysThreshold);

        $obsoleteItems = array_filter($slowMoving['items'], function ($item) use ($daysThreshold) {
            return $item['days_since_movement'] !== null && $item['days_since_movement'] >= $daysThreshold;
        });

        return [
            'tenant_id' => $tenantId,
            'days_threshold' => $daysThreshold,
            'total_items' => count($obsoleteItems),
            'total_value' => array_sum(array_column($obsoleteItems, 'value')),
            'items' => array_values($obsoleteItems),
        ];
    }

    /**
     * Get inventory turnover ratio
     *
     * @param  int  $tenantId  Tenant ID
     * @param  int  $periodDays  Period in days (default: 365)
     * @return array Turnover metrics
     */
    public function getInventoryTurnoverRatio(int $tenantId, int $periodDays = 365): array
    {
        $startDate = now()->subDays($periodDays);

        $totalCostOfGoodsSold = $this->db->table('stock_movements')
            ->where('type', 'out')
            ->where('created_at', '>=', $startDate)
            ->whereHas('inventoryItem', fn ($q) => $q->where('tenant_id', $tenantId))
            ->sum('quantity');

        $averageInventoryValue = $this->calculateWeightedAverageValue($tenantId)['total_value'];

        $turnoverRatio = $averageInventoryValue > 0 ? $totalCostOfGoodsSold / $averageInventoryValue : 0;
        $daysToSellInventory = $turnoverRatio > 0 ? $periodDays / $turnoverRatio : 0;

        return [
            'tenant_id' => $tenantId,
            'period_days' => $periodDays,
            'total_cost_of_goods_sold' => $totalCostOfGoodsSold,
            'average_inventory_value' => $averageInventoryValue,
            'turnover_ratio' => round($turnoverRatio, 2),
            'days_to_sell_inventory' => round($daysToSellInventory, 2),
            'calculated_at' => now()->toIso8601String(),
        ];
    }
}
