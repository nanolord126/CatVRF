<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Cache;
use Psr\Log\LoggerInterface;

/**
 * Stock Optimization Service
 *
 * Optimizes inventory levels and placement:
 * - Optimal stock level calculations
 * - Safety stock optimization
 * - Reorder point optimization
 * - Multi-echelon inventory optimization
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class StockOptimizationService
{
    use WithAuditLogging;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService,
        private readonly InventoryDomainService $domainService,
    ) {}

    /**
     * Optimize reorder points for all items in warehouse
     *
     * @param  int  $tenantId  Tenant ID
     * @param  int  $warehouseId  Warehouse ID
     * @param  int  $userId  User ID
     * @return array Optimization results
     */
    public function optimizeReorderPoints(int $tenantId, int $warehouseId, int $userId): array
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();

        $items = $this->db->table('inventory_items')
            ->where('tenant_id', $tenantId)
            ->where('warehouse_id', $warehouseId)
            ->where('is_active', true)
            ->get();

        $results = [];
        $optimizedCount = 0;

        foreach ($items as $item) {
            $leadTimeDays = $item->lead_time_days ?? 7;
            $serviceLevel = $this->determineOptimalServiceLevel($item);

            $oldReorderPoint = $item->min_stock_threshold;
            $newReorderPoint = $this->domainService->calculateReorderPoint(
                $item->id,
                $leadTimeDays,
                $serviceLevel
            );

            $results[] = [
                'inventory_item_id' => $item->id,
                'sku' => $item->sku,
                'old_reorder_point' => $oldReorderPoint,
                'new_reorder_point' => $newReorderPoint,
                'difference' => $newReorderPoint - $oldReorderPoint,
                'service_level' => $serviceLevel,
                'lead_time_days' => $leadTimeDays,
            ];

            if ($oldReorderPoint !== $newReorderPoint) {
                $optimizedCount++;
            }
        }

        $this->logAction(
            action: 'reorder_points_optimized',
            entityType: 'InventoryOptimization',
            entityId: 0,
            context: [
                'correlation_id' => $correlationId,
                'tenant_id' => $tenantId,
                'warehouse_id' => $warehouseId,
                'total_items' => count($items),
                'optimized_count' => $optimizedCount,
            ],
            userId: $userId,
            tenantId: $tenantId
        );

        return [
            'correlation_id' => $correlationId,
            'tenant_id' => $tenantId,
            'warehouse_id' => $warehouseId,
            'total_items' => count($items),
            'optimized_count' => $optimizedCount,
            'results' => $results,
        ];
    }

    /**
     * Optimize safety stock levels
     *
     * @param  int  $tenantId  Tenant ID
     * @param  int  $warehouseId  Warehouse ID
     * @param  int  $userId  User ID
     * @return array Optimization results
     */
    public function optimizeSafetyStock(int $tenantId, int $warehouseId, int $userId): array
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();

        $items = $this->db->table('inventory_items')
            ->where('tenant_id', $tenantId)
            ->where('warehouse_id', $warehouseId)
            ->where('is_active', true)
            ->get();

        $results = [];

        foreach ($items as $item) {
            $usageData = $this->getDailyUsage($item->id, 90);

            if (empty($usageData)) {
                continue;
            }

            $stdDev = $this->calculateStandardDeviation($usageData);
            $leadTimeDays = $item->lead_time_days ?? 7;
            $serviceLevel = $this->determineOptimalServiceLevel($item);

            $oldSafetyStock = $item->safety_stock ?? 0;
            $newSafetyStock = $this->domainService->calculateSafetyStock($stdDev, $leadTimeDays, $serviceLevel);

            $this->db->table('inventory_items')
                ->where('id', $item->id)
                ->update(['safety_stock' => (int) ceil($newSafetyStock)]);

            $results[] = [
                'inventory_item_id' => $item->id,
                'sku' => $item->sku,
                'old_safety_stock' => $oldSafetyStock,
                'new_safety_stock' => (int) ceil($newSafetyStock),
                'std_dev' => round($stdDev, 2),
                'service_level' => $serviceLevel,
            ];
        }

        $this->logAction(
            action: 'safety_stock_optimized',
            entityType: 'InventoryOptimization',
            entityId: 0,
            context: [
                'correlation_id' => $correlationId,
                'tenant_id' => $tenantId,
                'warehouse_id' => $warehouseId,
                'optimized_count' => count($results),
            ],
            userId: $userId,
            tenantId: $tenantId
        );

        return [
            'correlation_id' => $correlationId,
            'tenant_id' => $tenantId,
            'warehouse_id' => $warehouseId,
            'optimized_count' => count($results),
            'results' => $results,
        ];
    }

    /**
     * Calculate optimal order quantity considering constraints
     *
     * @param  int  $inventoryItemId  Inventory item ID
     * @param  int  $maxStorageSpace  Maximum storage space available
     * @param  float  $holdingCostRate  Holding cost rate
     * @param  float  $orderingCost  Ordering cost per order
     * @return array Optimization result
     */
    public function calculateOptimalOrderQuantity(
        int $inventoryItemId,
        int $maxStorageSpace,
        float $holdingCostRate = 0.25,
        float $orderingCost = 50.0
    ): array {
        $item = $this->db->table('inventory_items')
            ->where('id', $inventoryItemId)
            ->first();

        if (! $item) {
            throw new \RuntimeException("Inventory item {$inventoryItemId} not found");
        }

        $annualDemand = $item->annual_demand ?? $this->estimateAnnualDemand($inventoryItemId);
        $unitCost = $item->unit_cost ?? 0;

        if ($annualDemand <= 0 || $unitCost <= 0) {
            return [
                'inventory_item_id' => $inventoryItemId,
                'optimal_quantity' => 0,
                'method' => 'insufficient_data',
            ];
        }

        // EOQ calculation
        $holdingCost = $unitCost * $holdingCostRate;
        $eoq = sqrt((2 * $annualDemand * $orderingCost) / $holdingCost);
        $eoq = (int) round($eoq);

        // Constraint: storage space
        $spacePerUnit = $item->volume_per_unit ?? 1;
        $maxQtyBySpace = $maxStorageSpace / $spacePerUnit;

        // Constraint: supplier minimum order quantity
        $minOrderQty = $item->min_order_quantity ?? 1;

        // Apply constraints
        $optimalQty = max($minOrderQty, min($eoq, $maxQtyBySpace));

        return [
            'inventory_item_id' => $inventoryItemId,
            'annual_demand' => $annualDemand,
            'unit_cost' => $unitCost,
            'eoq' => $eoq,
            'optimal_quantity' => $optimalQty,
            'constraints' => [
                'max_storage_space' => $maxStorageSpace,
                'max_qty_by_space' => $maxQtyBySpace,
                'min_order_quantity' => $minOrderQty,
            ],
            'method' => 'constrained_eoq',
        ];
    }

    /**
     * Get stockout risk analysis
     *
     * @param  int  $tenantId  Tenant ID
     * @param  int  $warehouseId  Warehouse ID
     * @return array Risk analysis
     */
    public function getStockoutRiskAnalysis(int $tenantId, int $warehouseId): array
    {
        $items = $this->db->table('inventory_items')
            ->where('tenant_id', $tenantId)
            ->where('warehouse_id', $warehouseId)
            ->where('is_active', true)
            ->get();

        $highRiskItems = [];
        $mediumRiskItems = [];
        $lowRiskItems = [];

        foreach ($items as $item) {
            $currentStock = $item->current_stock;
            $safetyStock = $item->safety_stock ?? 0;
            $reorderPoint = $item->min_stock_threshold ?? 0;

            $dailyUsage = $this->getAverageDailyUsage($item->id, 30);
            $daysOfStock = $dailyUsage > 0 ? $currentStock / $dailyUsage : PHP_FLOAT_MAX;

            if ($currentStock <= $safetyStock) {
                $risk = 'high';
                $riskScore = 10;
            } elseif ($currentStock <= $reorderPoint) {
                $risk = 'medium';
                $riskScore = 6;
            } elseif ($daysOfStock < 7) {
                $risk = 'medium';
                $riskScore = 5;
            } else {
                $risk = 'low';
                $riskScore = 2;
            }

            $itemData = [
                'inventory_item_id' => $item->id,
                'sku' => $item->sku,
                'name' => $item->name,
                'current_stock' => $currentStock,
                'safety_stock' => $safetyStock,
                'reorder_point' => $reorderPoint,
                'daily_usage' => round($dailyUsage, 2),
                'days_of_stock' => $daysOfStock === PHP_FLOAT_MAX ? null : round($daysOfStock, 1),
                'risk_score' => $riskScore,
            ];

            if ($risk === 'high') {
                $highRiskItems[] = $itemData;
            } elseif ($risk === 'medium') {
                $mediumRiskItems[] = $itemData;
            } else {
                $lowRiskItems[] = $itemData;
            }
        }

        return [
            'tenant_id' => $tenantId,
            'warehouse_id' => $warehouseId,
            'total_items' => count($items),
            'high_risk_count' => count($highRiskItems),
            'medium_risk_count' => count($mediumRiskItems),
            'low_risk_count' => count($lowRiskItems),
            'high_risk_items' => $highRiskItems,
            'medium_risk_items' => $mediumRiskItems,
            'low_risk_items' => array_slice($lowRiskItems, 0, 20), // Limit for performance
        ];
    }

    /**
     * Determine optimal service level based on item characteristics
     *
     * @param  mixed  $item  Inventory item
     * @return float Service level (0.0-1.0)
     */
    private function determineOptimalServiceLevel($item): float
    {
        $abcClass = $item->abc_class ?? 'C';

        return match ($abcClass) {
            'A' => 0.99,
            'B' => 0.95,
            'C' => 0.90,
            default => 0.95,
        };
    }

    /**
     * Get daily usage data for item
     *
     * @param  int  $inventoryItemId  Inventory item ID
     * @param  int  $days  Number of days
     * @return array Daily usage quantities
     */
    private function getDailyUsage(int $inventoryItemId, int $days): array
    {
        $movements = $this->db->table('stock_movements')
            ->where('inventory_item_id', $inventoryItemId)
            ->where('type', 'out')
            ->where('created_at', '>=', now()->subDays($days))
            ->selectRaw('DATE(created_at) as date, SUM(ABS(quantity)) as usage')
            ->groupBy('date')
            ->get();

        $usage = [];
        for ($i = 0; $i < $days; $i++) {
            $date = now()->subDays($i)->toDateString();
            $movement = $movements->firstWhere('date', $date);
            $usage[] = $movement ? (float) $movement->usage : 0.0;
        }

        return array_reverse($usage);
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
        $usageData = $this->getDailyUsage($inventoryItemId, $days);

        return count($usageData) > 0 ? array_sum($usageData) / count($usageData) : 0.0;
    }

    /**
     * Calculate standard deviation
     *
     * @param  array  $values  Values
     * @return float Standard deviation
     */
    private function calculateStandardDeviation(array $values): float
    {
        if (count($values) < 2) {
            return 0.0;
        }

        $mean = array_sum($values) / count($values);
        $variance = array_sum(array_map(function ($x) use ($mean) {
            return pow($x - $mean, 2);
        }, $values)) / count($values);

        return sqrt($variance);
    }

    /**
     * Estimate annual demand based on historical data
     *
     * @param  int  $inventoryItemId  Inventory item ID
     * @return int Estimated annual demand
     */
    private function estimateAnnualDemand(int $inventoryItemId): int
    {
        $totalUsage = $this->db->table('stock_movements')
            ->where('inventory_item_id', $inventoryItemId)
            ->where('type', 'out')
            ->where('created_at', '>=', now()->subDays(90))
            ->sum('quantity');

        return (int) ($totalUsage * 4); // Extrapolate 90 days to 365
    }
}
