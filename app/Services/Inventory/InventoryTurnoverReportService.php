<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;
use Illuminate\Database\DatabaseManager;
use Illuminate\Cache\Repository as CacheRepository;
use Psr\Log\LoggerInterface;

/**
 * Inventory Turnover Report Service
 *
 * Generates turnover reports including:
 * - Item turnover rate
 * - Days sales of inventory (DSI)
 * - Inventory turnover ratio
 * - Period comparison
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class InventoryTurnoverReportService
{
    use WithAuditLogging;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService,
        private readonly CacheRepository $cache
    ) {}

    /**
     * Calculate turnover rate for item
     *
     * @param  int  $inventoryItemId  Inventory item ID
     * @param  int  $days  Period in days
     * @return array Turnover data
     */
    public function calculateItemTurnover(int $inventoryItemId, int $days = 90): array
    {
        $item = $this->db->table('inventory_items')
            ->where('id', $inventoryItemId)
            ->first();

        if (! $item) {
            throw new \RuntimeException("Inventory item not found: {$inventoryItemId}");
        }

        $startDate = now()->subDays($days);

        $totalSold = $this->db->table('stock_movements')
            ->where('inventory_item_id', $inventoryItemId)
            ->where('type', 'out')
            ->where('created_at', '>=', $startDate)
            ->selectRaw('SUM(ABS(quantity)) as total')
            ->value('total');

        $totalSold = (float) ($totalSold ?? 0);

        $averageInventory = $this->calculateAverageInventory($inventoryItemId, $days);
        $costOfGoodsSold = $totalSold * ($item->unit_cost ?? 0);

        $turnoverRate = $averageInventory > 0 ? $costOfGoodsSold / $averageInventory : 0;
        $daysOfInventory = $turnoverRate > 0 ? $days / $turnoverRate : 0;

        return [
            'inventory_item_id' => $inventoryItemId,
            'period_days' => $days,
            'total_sold' => $totalSold,
            'average_inventory' => $averageInventory,
            'cost_of_goods_sold' => $costOfGoodsSold,
            'turnover_rate' => $turnoverRate,
            'days_of_inventory' => $daysOfInventory,
            'turns_per_period' => $turnoverRate,
            'current_stock' => $item->current_stock,
        ];
    }

    /**
     * Generate turnover report for warehouse
     *
     * @param  int  $warehouseId  Warehouse ID
     * @param  int  $days  Period in days
     * @return array Turnover report
     */
    public function generateWarehouseTurnoverReport(int $warehouseId, int $days = 90): array
    {
        $items = $this->db->table('inventory_items')
            ->where('warehouse_id', $warehouseId)
            ->get();

        $turnoverData = [];

        foreach ($items as $item) {
            $turnoverData[] = $this->calculateItemTurnover($item->id, $days);
        }

        $totalInventoryValue = $items->sum(function ($item) {
            return $item->current_stock * ($item->unit_cost ?? 0);
        });

        $totalCostOfGoodsSold = array_sum(array_column($turnoverData, 'cost_of_goods_sold'));
        $overallTurnoverRate = $totalInventoryValue > 0 ? $totalCostOfGoodsSold / $totalInventoryValue : 0;

        $sortedByTurnover = collect($turnoverData)->sortByDesc('turnover_rate')->values();

        $fastMoving = $sortedByTurnover->take((int) (count($sortedByTurnover) * 0.2));
        $slowMoving = $sortedByTurnover->takeLast((int) (count($sortedByTurnover) * 0.2));

        return [
            'warehouse_id' => $warehouseId,
            'period_days' => $days,
            'summary' => [
                'total_items' => count($turnoverData),
                'total_inventory_value' => $totalInventoryValue,
                'total_cost_of_goods_sold' => $totalCostOfGoodsSold,
                'overall_turnover_rate' => $overallTurnoverRate,
                'average_turnover_rate' => count($turnoverData) > 0
                    ? array_sum(array_column($turnoverData, 'turnover_rate')) / count($turnoverData)
                    : 0,
            ],
            'fast_moving_items' => $fastMoving->toArray(),
            'slow_moving_items' => $slowMoving->toArray(),
            'all_items' => $sortedByTurnover->toArray(),
        ];
    }

    /**
     * Generate turnover trend report
     *
     * @param  int  $warehouseId  Warehouse ID
     * @param  int  $periods  Number of periods
     * @param  int  $daysPerPeriod  Days per period
     * @return array Trend data
     */
    public function generateTurnoverTrendReport(int $warehouseId, int $periods = 12, int $daysPerPeriod = 30): array
    {
        $trends = [];

        for ($i = 0; $i < $periods; $i++) {
            $endDate = now()->subDays($i * $daysPerPeriod);
            $startDate = $endDate->copy()->subDays($daysPerPeriod);

            $totalSold = $this->db->table('stock_movements as sm')
                ->join('inventory_items as ii', 'sm.inventory_item_id', '=', 'ii.id')
                ->where('ii.warehouse_id', $warehouseId)
                ->where('sm.type', 'out')
                ->where('sm.created_at', '>=', $startDate)
                ->where('sm.created_at', '<=', $endDate)
                ->selectRaw('SUM(ABS(sm.quantity) * ii.unit_cost) as total_cost')
                ->value('total_cost');

            $totalInventoryValue = $this->db->table('inventory_items')
                ->where('warehouse_id', $warehouseId)
                ->selectRaw('SUM(current_stock * unit_cost) as total_value')
                ->value('total_value');

            $turnoverRate = $totalInventoryValue > 0
                ? ((float) ($totalSold ?? 0)) / $totalInventoryValue
                : 0;

            $trends[] = [
                'period' => $i + 1,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'total_cost_of_goods_sold' => (float) ($totalSold ?? 0),
                'total_inventory_value' => $totalInventoryValue,
                'turnover_rate' => $turnoverRate,
            ];
        }

        $trends = array_reverse($trends);

        return [
            'warehouse_id' => $warehouseId,
            'periods' => $periods,
            'days_per_period' => $daysPerPeriod,
            'trends' => $trends,
            'average_turnover_rate' => count($trends) > 0
                ? array_sum(array_column($trends, 'turnover_rate')) / count($trends)
                : 0,
        ];
    }

    /**
     * Calculate average inventory for item over period
     *
     * @param  int  $inventoryItemId  Inventory item ID
     * @param  int  $days  Period in days
     * @return float Average inventory value
     */
    private function calculateAverageInventory(int $inventoryItemId, int $days): float
    {
        $item = $this->db->table('inventory_items')
            ->where('id', $inventoryItemId)
            ->first();

        if (! $item) {
            return 0.0;
        }

        $startDate = now()->subDays($days);

        $movements = $this->db->table('stock_movements')
            ->where('inventory_item_id', $inventoryItemId)
            ->where('created_at', '>=', $startDate)
            ->orderBy('created_at')
            ->get();

        if ($movements->isEmpty()) {
            return (float) ($item->current_stock * ($item->unit_cost ?? 0));
        }

        $dailyValues = [];
        $currentStock = $item->current_stock;
        $currentDate = now();

        for ($i = 0; $i < $days; $i++) {
            $date = $currentDate->copy()->subDays($i);
            $dayMovements = $movements->filter(function ($m) use ($date) {
                return $m->created_at->toDateString() === $date->toDateString();
            });

            foreach ($dayMovements as $movement) {
                if ($movement->type === 'in') {
                    $currentStock -= $movement->quantity;
                } elseif ($movement->type === 'out') {
                    $currentStock += abs($movement->quantity);
                }
            }

            $dailyValues[] = $currentStock * ($item->unit_cost ?? 0);
        }

        return count($dailyValues) > 0 ? array_sum($dailyValues) / count($dailyValues) : 0;
    }

    /**
     * Generate category turnover report
     *
     * @param  int  $warehouseId  Warehouse ID
     * @param  int  $days  Period in days
     * @return array Category turnover data
     */
    public function generateCategoryTurnoverReport(int $warehouseId, int $days = 90): array
    {
        $startDate = now()->subDays($days);

        $items = $this->db->table('inventory_items')
            ->where('warehouse_id', $warehouseId)
            ->get();

        $categoryData = [];

        foreach ($items as $item) {
            $category = $item->category ?? 'uncategorized';

            if (! isset($categoryData[$category])) {
                $categoryData[$category] = [
                    'category' => $category,
                    'items_count' => 0,
                    'total_inventory_value' => 0,
                    'total_cost_of_goods_sold' => 0,
                ];
            }

            $categoryData[$category]['items_count']++;
            $categoryData[$category]['total_inventory_value'] += $item->current_stock * ($item->unit_cost ?? 0);

            $sold = $this->db->table('stock_movements')
                ->where('inventory_item_id', $item->id)
                ->where('type', 'out')
                ->where('created_at', '>=', $startDate)
                ->selectRaw('SUM(ABS(quantity) * ?) as total_cost', [$item->unit_cost ?? 0])
                ->value('total_cost');

            $categoryData[$category]['total_cost_of_goods_sold'] += (float) ($sold ?? 0);
        }

        foreach ($categoryData as &$category) {
            $category['turnover_rate'] = $category['total_inventory_value'] > 0
                ? $category['total_cost_of_goods_sold'] / $category['total_inventory_value']
                : 0;
        }

        $sortedCategories = collect($categoryData)->sortByDesc('turnover_rate')->values();

        return [
            'warehouse_id' => $warehouseId,
            'period_days' => $days,
            'categories' => $sortedCategories->toArray(),
        ];
    }

    /**
     * Export turnover report to CSV format
     *
     * @param  int  $warehouseId  Warehouse ID
     * @param  int  $days  Period in days
     * @return string CSV content
     */
    public function exportToCsv(int $warehouseId, int $days = 90): string
    {
        $report = $this->generateWarehouseTurnoverReport($warehouseId, $days);

        $lines = [];
        $lines[] = 'Item ID,SKU,Name,Current Stock,Average Inventory,Total Sold,Cost of Goods Sold,Turnover Rate,Days of Inventory';

        foreach ($report['all_items'] as $item) {
            $inventoryItem = $this->db->table('inventory_items')
                ->where('id', $item['inventory_item_id'])
                ->first();

            $lines[] = sprintf(
                '%s,%s,%s,%s,%s,%s,%s,%s,%s',
                $item['inventory_item_id'],
                $inventoryItem->sku ?? '',
                $inventoryItem->name ?? '',
                $item['current_stock'],
                number_format($item['average_inventory'], 2),
                $item['total_sold'],
                number_format($item['cost_of_goods_sold'], 2),
                number_format($item['turnover_rate'], 4),
                number_format($item['days_of_inventory'], 2)
            );
        }

        return implode("\n", $lines);
    }
}
