<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Cache;
use Psr\Log\LoggerInterface;

/**
 * Inventory Analytics Service
 *
 * Provides advanced analytics for inventory:
 * - ABC analysis
 * - XYZ analysis
 * - Inventory turnover analysis
 * - Stockout analysis
 * - Carry cost analysis
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class InventoryAnalyticsService
{
    use WithAuditLogging;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Perform ABC-XYZ analysis
     *
     * ABC: Classification by value (A=high value, B=medium, C=low)
     * XYZ: Classification by demand variability (X=stable, Y=variable, Z=erratic)
     *
     * @param  int  $tenantId  Tenant ID
     * @param  int|null  $warehouseId  Warehouse ID (optional)
     * @return array Analysis results
     */
    public function performABCXYZAnalysis(int $tenantId, ?int $warehouseId = null): array
    {
        $cacheKey = "inventory_analytics:abc_xyz:{$tenantId}:{$warehouseId}";

        return Cache::remember($cacheKey, 3600, function () use ($tenantId, $warehouseId) {
            $query = $this->db->table('inventory_items')
                ->where('tenant_id', $tenantId)
                ->where('is_active', true);

            if ($warehouseId) {
                $query->where('warehouse_id', $warehouseId);
            }

            $items = $query->get();

            $totalValue = $items->sum(fn ($i) => $i->current_stock * ($i->unit_cost ?? 0));

            $itemData = [];

            foreach ($items as $item) {
                $value = $item->current_stock * ($item->unit_cost ?? 0);
                $demandVariability = $this->calculateDemandVariability($item->id, 90);

                $itemData[] = [
                    'id' => $item->id,
                    'sku' => $item->sku,
                    'name' => $item->name,
                    'value' => $value,
                    'demand_variability' => $demandVariability,
                    'current_stock' => $item->current_stock,
                ];
            }

            usort($itemData, fn ($a, $b) => $b['value'] <=> $a['value']);

            $cumulativeValue = 0;
            $classified = [];

            foreach ($itemData as $item) {
                $cumulativeValue += $item['value'];
                $cumulativePercentage = ($cumulativeValue / $totalValue) * 100;

                if ($cumulativePercentage <= 70) {
                    $abcClass = 'A';
                } elseif ($cumulativePercentage <= 90) {
                    $abcClass = 'B';
                } else {
                    $abcClass = 'C';
                }

                $xyzClass = $this->classifyXYZ($item['demand_variability']);

                $classified[] = array_merge($item, [
                    'abc_class' => $abcClass,
                    'xyz_class' => $xyzClass,
                    'combined_class' => $abcClass.$xyzClass,
                ]);
            }

            $matrix = $this->buildABCXYZMatrix($classified);

            return [
                'tenant_id' => $tenantId,
                'warehouse_id' => $warehouseId,
                'total_items' => count($classified),
                'total_value' => $totalValue,
                'matrix' => $matrix,
                'items' => $classified,
                'generated_at' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * Calculate inventory turnover by category
     *
     * @param  int  $tenantId  Tenant ID
     * @param  int  $days  Number of days (default: 365)
     * @return array Turnover data
     */
    public function calculateTurnoverByCategory(int $tenantId, int $days = 365): array
    {
        $startDate = now()->subDays($days)->toDateString();

        $categories = $this->db->table('inventory_items as i')
            ->join('products as p', 'i.product_id', '=', 'p.id')
            ->where('i.tenant_id', $tenantId)
            ->select('p.category_id')
            ->distinct()
            ->pluck('category_id')
            ->filter()
            ->toArray();

        $turnoverData = [];

        foreach ($categories as $categoryId) {
            $totalSold = $this->db->table('stock_movements as m')
                ->join('inventory_items as i', 'm.inventory_item_id', '=', 'i.id')
                ->join('products as p', 'i.product_id', '=', 'p.id')
                ->where('p.category_id', $categoryId)
                ->where('m.type', 'out')
                ->where('m.created_at', '>=', $startDate)
                ->where('i.tenant_id', $tenantId)
                ->sum('m.quantity');

            $avgInventory = $this->db->table('inventory_items as i')
                ->join('products as p', 'i.product_id', '=', 'p.id')
                ->where('p.category_id', $categoryId)
                ->where('i.tenant_id', $tenantId)
                ->avg('i.current_stock') ?? 0;

            $turnoverRate = $avgInventory > 0 ? ($totalSold / $avgInventory) : 0;
            $daysToSell = $turnoverRate > 0 ? $days / $turnoverRate : 0;

            $turnoverData[] = [
                'category_id' => $categoryId,
                'total_sold' => $totalSold,
                'average_inventory' => round($avgInventory, 2),
                'turnover_rate' => round($turnoverRate, 2),
                'days_to_sell' => round($daysToSell, 2),
            ];
        }

        usort($turnoverData, fn ($a, $b) => $b['turnover_rate'] <=> $a['turnover_rate']);

        return [
            'tenant_id' => $tenantId,
            'period_days' => $days,
            'categories' => $turnoverData,
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Analyze stockout patterns
     *
     * @param  int  $tenantId  Tenant ID
     * @param  int  $days  Number of days (default: 90)
     * @return array Stockout analysis
     */
    public function analyzeStockoutPatterns(int $tenantId, int $days = 90): array
    {
        $startDate = now()->subDays($days)->toDateString();

        $stockoutEvents = $this->db->table('inventory_alerts as a')
            ->join('inventory_items as i', 'a.inventory_item_id', '=', 'i.id')
            ->where('a.tenant_id', $tenantId)
            ->where('a.alert_type', 'stockout')
            ->where('a.created_at', '>=', $startDate)
            ->select('a.*', 'i.sku', 'i.name', 'i.abc_class')
            ->get();

        $stockoutsByItem = $stockoutEvents->groupBy('inventory_item_id')
            ->map(function ($events) {
                return [
                    'inventory_item_id' => $events->first()->inventory_item_id,
                    'sku' => $events->first()->sku,
                    'name' => $events->first()->name,
                    'abc_class' => $events->first()->abc_class,
                    'stockout_count' => $events->count(),
                    'first_stockout' => $events->min('created_at')->toIso8601String(),
                    'last_stockout' => $events->max('created_at')->toIso8601String(),
                ];
            })
            ->sortByDesc('stockout_count')
            ->values()
            ->toArray();

        $stockoutsByDay = $stockoutEvents->groupBy(function ($event) {
            return $event->created_at->toDateString();
        })->map(fn ($events) => [
            'date' => $events->first()->created_at->toDateString(),
            'count' => $events->count(),
        ])->toArray();

        $stockoutsByABC = [
            'A' => $stockoutEvents->where('abc_class', 'A')->count(),
            'B' => $stockoutEvents->where('abc_class', 'B')->count(),
            'C' => $stockoutEvents->where('abc_class', 'C')->count(),
        ];

        return [
            'tenant_id' => $tenantId,
            'period_days' => $days,
            'total_stockout_events' => $stockoutEvents->count(),
            'stockouts_by_item' => array_slice($stockoutsByItem, 0, 20),
            'stockouts_by_day' => $stockoutsByDay,
            'stockouts_by_abc_class' => $stockoutsByABC,
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Calculate inventory carry cost
     *
     * @param  int  $tenantId  Tenant ID
     * @param  float  $annualCarryRate  Annual carry rate (default: 0.25 = 25%)
     * @return array Carry cost analysis
     */
    public function calculateCarryCost(int $tenantId, float $annualCarryRate = 0.25): array
    {
        $items = $this->db->table('inventory_items')
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get();

        $totalValue = 0;
        $totalCarryCost = 0;
        $itemCarryCosts = [];

        foreach ($items as $item) {
            $value = $item->current_stock * ($item->unit_cost ?? 0);
            $carryCost = $value * $annualCarryRate;

            $totalValue += $value;
            $totalCarryCost += $carryCost;

            $itemCarryCosts[] = [
                'inventory_item_id' => $item->id,
                'sku' => $item->sku,
                'name' => $item->name,
                'value' => $value,
                'carry_cost' => $carryCost,
                'carry_cost_percentage' => $annualCarryRate * 100,
            ];
        }

        usort($itemCarryCosts, fn ($a, $b) => $b['carry_cost'] <=> $a['carry_cost']);

        return [
            'tenant_id' => $tenantId,
            'annual_carry_rate' => $annualCarryRate,
            'total_inventory_value' => $totalValue,
            'total_annual_carry_cost' => $totalCarryCost,
            'monthly_carry_cost' => $totalCarryCost / 12,
            'top_carriers' => array_slice($itemCarryCosts, 0, 20),
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Calculate demand variability
     *
     * @param  int  $inventoryItemId  Inventory item ID
     * @param  int  $days  Number of days
     * @return float Coefficient of variation
     */
    private function calculateDemandVariability(int $inventoryItemId, int $days): float
    {
        $demandData = $this->getDailyDemand($inventoryItemId, $days);

        if (count($demandData) < 2) {
            return 0.0;
        }

        $mean = array_sum($demandData) / count($demandData);
        $variance = array_sum(array_map(fn ($x) => pow($x - $mean, 2), $demandData)) / count($demandData);
        $stdDev = sqrt($variance);

        return $mean > 0 ? ($stdDev / $mean) * 100 : 0.0;
    }

    /**
     * Classify item as X, Y, or Z based on demand variability
     *
     * @param  float  $variability  Coefficient of variation
     * @return string XYZ class
     */
    private function classifyXYZ(float $variability): string
    {
        if ($variability <= 20) {
            return 'X'; // Stable demand
        }

        if ($variability <= 50) {
            return 'Y'; // Variable demand
        }

        return 'Z'; // Erratic demand
    }

    /**
     * Build ABC-XYZ matrix
     *
     * @param  array  $classifiedItems  Classified items
     * @return array Matrix
     */
    private function buildABCXYZMatrix(array $classifiedItems): array
    {
        $matrix = [
            'AX' => ['count' => 0, 'items' => []],
            'AY' => ['count' => 0, 'items' => []],
            'AZ' => ['count' => 0, 'items' => []],
            'BX' => ['count' => 0, 'items' => []],
            'BY' => ['count' => 0, 'items' => []],
            'BZ' => ['count' => 0, 'items' => []],
            'CX' => ['count' => 0, 'items' => []],
            'CY' => ['count' => 0, 'items' => []],
            'CZ' => ['count' => 0, 'items' => []],
        ];

        foreach ($classifiedItems as $item) {
            $combinedClass = $item['combined_class'];
            $matrix[$combinedClass]['count']++;
            $matrix[$combinedClass]['items'][] = [
                'sku' => $item['sku'],
                'name' => $item['name'],
                'value' => $item['value'],
            ];
        }

        return $matrix;
    }

    /**
     * Get daily demand data
     *
     * @param  int  $inventoryItemId  Inventory item ID
     * @param  int  $days  Number of days
     * @return array Daily demand
     */
    private function getDailyDemand(int $inventoryItemId, int $days): array
    {
        $movements = $this->db->table('stock_movements')
            ->where('inventory_item_id', $inventoryItemId)
            ->where('type', 'out')
            ->where('created_at', '>=', now()->subDays($days))
            ->selectRaw('DATE(created_at) as date, SUM(ABS(quantity)) as demand')
            ->groupBy('date')
            ->get();

        $demand = [];
        for ($i = 0; $i < $days; $i++) {
            $date = now()->subDays($i)->toDateString();
            $movement = $movements->firstWhere('date', $date);
            $demand[] = $movement ? (float) $movement->demand : 0.0;
        }

        return array_reverse($demand);
    }
}
