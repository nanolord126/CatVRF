<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Cache;
use Psr\Log\LoggerInterface;

/**
 * Inventory Reporting Service
 *
 * Generates comprehensive inventory reports:
 * - Stock status reports
 * - Movement reports
 * - Performance reports
 * - Compliance reports
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class InventoryReportingService
{
    use WithAuditLogging;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Generate stock status report
     *
     * @param  int  $tenantId  Tenant ID
     * @param  int|null  $warehouseId  Warehouse ID (optional)
     * @return array Report data
     */
    public function generateStockStatusReport(int $tenantId, ?int $warehouseId = null): array
    {
        $cacheKey = "inventory_report:stock_status:{$tenantId}:{$warehouseId}";

        return Cache::remember($cacheKey, 600, function () use ($tenantId, $warehouseId) {
            $query = $this->db->table('inventory_items')
                ->where('tenant_id', $tenantId)
                ->where('is_active', true);

            if ($warehouseId) {
                $query->where('warehouse_id', $warehouseId);
            }

            $items = $query->get();

            $totalItems = $items->count();
            $totalStock = $items->sum('current_stock');
            $totalValue = $items->sum(fn ($i) => $i->current_stock * ($i->unit_cost ?? 0));
            $totalReserved = $items->sum('reserved_stock');

            $inStockItems = $items->filter(fn ($i) => $i->current_stock > 0)->count();
            $outOfStockItems = $items->filter(fn ($i) => $i->current_stock <= 0)->count();
            $lowStockItems = $items->filter(fn ($i) => $i->current_stock <= $i->min_stock_threshold && $i->current_stock > 0)->count();

            $abcDistribution = [
                'A' => $items->where('abc_class', 'A')->count(),
                'B' => $items->where('abc_class', 'B')->count(),
                'C' => $items->where('abc_class', 'C')->count(),
            ];

            return [
                'report_type' => 'stock_status',
                'tenant_id' => $tenantId,
                'warehouse_id' => $warehouseId,
                'generated_at' => now()->toIso8601String(),
                'summary' => [
                    'total_items' => $totalItems,
                    'in_stock_items' => $inStockItems,
                    'out_of_stock_items' => $outOfStockItems,
                    'low_stock_items' => $lowStockItems,
                    'total_stock_quantity' => $totalStock,
                    'total_reserved_quantity' => $totalReserved,
                    'total_available_quantity' => $totalStock - $totalReserved,
                    'total_value' => $totalValue,
                ],
                'abc_distribution' => $abcDistribution,
                'stock_health' => [
                    'in_stock_rate' => $totalItems > 0 ? round(($inStockItems / $totalItems) * 100, 2) : 0,
                    'out_of_stock_rate' => $totalItems > 0 ? round(($outOfStockItems / $totalItems) * 100, 2) : 0,
                    'low_stock_rate' => $totalItems > 0 ? round(($lowStockItems / $totalItems) * 100, 2) : 0,
                ],
            ];
        });
    }

    /**
     * Generate movement report
     *
     * @param  int  $tenantId  Tenant ID
     * @param  string  $startDate  Start date
     * @param  string  $endDate  End date
     * @param  int|null  $warehouseId  Warehouse ID (optional)
     * @return array Report data
     */
    public function generateMovementReport(
        int $tenantId,
        string $startDate,
        string $endDate,
        ?int $warehouseId = null
    ): array {
        $cacheKey = "inventory_report:movement:{$tenantId}:{$startDate}:{$endDate}:{$warehouseId}";

        return Cache::remember($cacheKey, 600, function () use (
            $tenantId,
            $startDate,
            $endDate,
            $warehouseId
        ) {
            $query = $this->db->table('stock_movements as m')
                ->join('inventory_items as i', 'm.inventory_item_id', '=', 'i.id')
                ->where('i.tenant_id', $tenantId)
                ->whereBetween('m.created_at', [$startDate, $endDate]);

            if ($warehouseId) {
                $query->where('i.warehouse_id', $warehouseId);
            }

            $movements = $query->select('m.*')->get();

            $movementsByType = $movements->groupBy('type')->map(fn ($group) => [
                'count' => $group->count(),
                'total_quantity' => $group->sum('quantity'),
            ])->toArray();

            $totalIn = abs($movements->where('type', 'in')->sum('quantity'));
            $totalOut = abs($movements->where('type', 'out')->sum('quantity'));
            $netChange = $totalIn - $totalOut;

            $topItems = $movements->groupBy('inventory_item_id')
                ->map(fn ($group) => [
                    'inventory_item_id' => $group->first()->inventory_item_id,
                    'movement_count' => $group->count(),
                    'total_quantity' => $group->sum(fn ($m) => abs($m->quantity)),
                ])
                ->sortByDesc('total_quantity')
                ->take(20)
                ->values()
                ->toArray();

            return [
                'report_type' => 'movement',
                'tenant_id' => $tenantId,
                'warehouse_id' => $warehouseId,
                'period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ],
                'generated_at' => now()->toIso8601String(),
                'summary' => [
                    'total_movements' => $movements->count(),
                    'total_in' => $totalIn,
                    'total_out' => $totalOut,
                    'net_change' => $netChange,
                ],
                'movements_by_type' => $movementsByType,
                'top_moving_items' => $topItems,
            ];
        });
    }

    /**
     * Generate performance report
     *
     * @param  int  $tenantId  Tenant ID
     * @param  int  $days  Number of days (default: 30)
     * @param  int|null  $warehouseId  Warehouse ID (optional)
     * @return array Report data
     */
    public function generatePerformanceReport(
        int $tenantId,
        int $days = 30,
        ?int $warehouseId = null
    ): array {
        $cacheKey = "inventory_report:performance:{$tenantId}:{$days}:{$warehouseId}";

        return Cache::remember($cacheKey, 600, function () use ($tenantId, $days, $warehouseId) {
            $startDate = now()->subDays($days)->toDateString();
            $endDate = now()->toDateString();

            $movementReport = $this->generateMovementReport($tenantId, $startDate, $endDate, $warehouseId);
            $stockStatusReport = $this->generateStockStatusReport($tenantId, $warehouseId);

            $totalStockouts = $this->db->table('stock_movements as m')
                ->join('inventory_items as i', 'm.inventory_item_id', '=', 'i.id')
                ->where('i.tenant_id', $tenantId)
                ->where('m.type', 'out')
                ->whereBetween('m.created_at', [$startDate, $endDate]);

            if ($warehouseId) {
                $totalStockouts->where('i.warehouse_id', $warehouseId);
            }

            $stockoutItems = $totalStockouts
                ->select('m.inventory_item_id')
                ->distinct()
                ->get()
                ->count();

            $avgDailyMovement = $movementReport['summary']['total_movements'] / $days;

            return [
                'report_type' => 'performance',
                'tenant_id' => $tenantId,
                'warehouse_id' => $warehouseId,
                'period_days' => $days,
                'generated_at' => now()->toIso8601String(),
                'metrics' => [
                    'avg_daily_movements' => round($avgDailyMovement, 2),
                    'stockout_items_count' => $stockoutItems,
                    'stockout_rate' => $stockStatusReport['summary']['total_items'] > 0
                        ? round(($stockoutItems / $stockStatusReport['summary']['total_items']) * 100, 2)
                        : 0,
                    'inventory_turnover' => $this->calculateInventoryTurnover($tenantId, $days, $warehouseId),
                    'fill_rate' => $this->calculateFillRate($tenantId, $days, $warehouseId),
                ],
                'movement_summary' => $movementReport['summary'],
                'stock_status_summary' => $stockStatusReport['summary'],
            ];
        });
    }

    /**
     * Generate compliance report
     *
     * @param  int  $tenantId  Tenant ID
     * @param  int|null  $warehouseId  Warehouse ID (optional)
     * @return array Report data
     */
    public function generateComplianceReport(int $tenantId, ?int $warehouseId = null): array
    {
        $cacheKey = "inventory_report:compliance:{$tenantId}:{$warehouseId}";

        return Cache::remember($cacheKey, 600, function () use ($tenantId, $warehouseId) {
            $query = $this->db->table('inventory_items')
                ->where('tenant_id', $tenantId)
                ->where('is_active', true);

            if ($warehouseId) {
                $query->where('warehouse_id', $warehouseId);
            }

            $items = $query->get();

            $complianceIssues = [];

            // Check for items without SKU
            $noSku = $items->filter(fn ($i) => empty($i->sku))->count();
            if ($noSku > 0) {
                $complianceIssues[] = [
                    'type' => 'missing_sku',
                    'severity' => 'high',
                    'count' => $noSku,
                    'message' => "{$noSku} items missing SKU",
                ];
            }

            // Check for items without unit cost
            $noCost = $items->filter(fn ($i) => $i->unit_cost === null || $i->unit_cost <= 0)->count();
            if ($noCost > 0) {
                $complianceIssues[] = [
                    'type' => 'missing_unit_cost',
                    'severity' => 'medium',
                    'count' => $noCost,
                    'message' => "{$noCost} items missing unit cost",
                ];
            }

            // Check for expired batches (for medical products)
            $expiredBatches = $this->db->table('inventory_batches as b')
                ->join('inventory_items as i', 'b.inventory_item_id', '=', 'i.id')
                ->where('i.tenant_id', $tenantId)
                ->where('b.expiry_date', '<', now())
                ->where('b.quantity', '>', 0);

            if ($warehouseId) {
                $expiredBatches->where('i.warehouse_id', $warehouseId);
            }

            $expiredCount = $expiredBatches->count();
            if ($expiredCount > 0) {
                $complianceIssues[] = [
                    'type' => 'expired_batches',
                    'severity' => 'critical',
                    'count' => $expiredCount,
                    'message' => "{$expiredCount} expired batches with stock",
                ];
            }

            // Check for items without ABC classification
            $noAbc = $items->filter(fn ($i) => empty($i->abc_class))->count();
            if ($noAbc > 0) {
                $complianceIssues[] = [
                    'type' => 'missing_abc_classification',
                    'severity' => 'low',
                    'count' => $noAbc,
                    'message' => "{$noAbc} items missing ABC classification",
                ];
            }

            return [
                'report_type' => 'compliance',
                'tenant_id' => $tenantId,
                'warehouse_id' => $warehouseId,
                'generated_at' => now()->toIso8601String(),
                'total_items' => $items->count(),
                'compliance_score' => $this->calculateComplianceScore($items, $complianceIssues),
                'issues' => $complianceIssues,
                'is_compliant' => empty($complianceIssues),
            ];
        });
    }

    /**
     * Calculate inventory turnover rate
     *
     * @param  int  $tenantId  Tenant ID
     * @param  int  $days  Number of days
     * @param  int|null  $warehouseId  Warehouse ID
     * @return float Turnover rate
     */
    private function calculateInventoryTurnover(int $tenantId, int $days, ?int $warehouseId): float
    {
        $startDate = now()->subDays($days)->toDateString();

        $query = $this->db->table('stock_movements as m')
            ->join('inventory_items as i', 'm.inventory_item_id', '=', 'i.id')
            ->where('i.tenant_id', $tenantId)
            ->where('m.type', 'out')
            ->where('m.created_at', '>=', $startDate);

        if ($warehouseId) {
            $query->where('i.warehouse_id', $warehouseId);
        }

        $totalOut = abs($query->sum('m.quantity'));

        $avgInventoryQuery = $this->db->table('inventory_items')
            ->where('tenant_id', $tenantId);

        if ($warehouseId) {
            $avgInventoryQuery->where('warehouse_id', $warehouseId);
        }

        $avgInventory = $avgInventoryQuery->avg('current_stock') ?? 0;

        return $avgInventory > 0 ? ($totalOut / $avgInventory) * (365 / $days) : 0.0;
    }

    /**
     * Calculate fill rate
     *
     * @param  int  $tenantId  Tenant ID
     * @param  int  $days  Number of days
     * @param  int|null  $warehouseId  Warehouse ID
     * @return float Fill rate percentage
     */
    private function calculateFillRate(int $tenantId, int $days, ?int $warehouseId): float
    {
        $startDate = now()->subDays($days)->toDateString();

        $query = $this->db->table('inventory_allocations as a')
            ->join('inventory_items as i', 'a.inventory_item_id', '=', 'i.id')
            ->where('i.tenant_id', $tenantId)
            ->where('a.created_at', '>=', $startDate);

        if ($warehouseId) {
            $query->where('i.warehouse_id', $warehouseId);
        }

        $allocations = $query->get();

        if ($allocations->isEmpty()) {
            return 100.0;
        }

        $totalRequested = $allocations->sum('quantity');
        $fullyFilled = $allocations->where('status', 'allocated')->count();

        return $totalRequested > 0 ? ($fullyFilled / $allocations->count()) * 100 : 100.0;
    }

    /**
     * Calculate compliance score (0-100)
     *
     * @param  mixed  $items  Items collection
     * @param  array  $issues  Compliance issues
     * @return float Compliance score
     */
    private function calculateComplianceScore($items, array $issues): float
    {
        if ($items->isEmpty()) {
            return 100.0;
        }

        $totalDeductions = 0;

        foreach ($issues as $issue) {
            $totalDeductions += match ($issue['severity']) {
                'critical' => 30,
                'high' => 20,
                'medium' => 10,
                'low' => 5,
                default => 0,
            };
        }

        return max(0, 100 - $totalDeductions);
    }
}
