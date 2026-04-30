<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;
use Illuminate\Database\DatabaseManager;
use Illuminate\Cache\Repository as CacheRepository;
use Psr\Log\LoggerInterface;

/**
 * ABC-XYZ Analysis Service
 *
 * Combines ABC analysis (value-based) with XYZ analysis (demand variability)
 * to provide comprehensive inventory classification:
 * - ABC: A (high value), B (medium value), C (low value)
 * - X: Stable demand
 * - Y: Variable demand
 * - Z: Irregular/sporadic demand
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class ABCXYZAnalysisService
{
    use WithAuditLogging;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService,
        private readonly CacheRepository $cache
    ) {}

    /**
     * Perform ABC-XYZ analysis for tenant
     *
     * @param  int  $tenantId  Tenant ID
     * @param  int  $days  Analysis period in days
     * @return array ABC-XYZ classification results
     */
    public function performABCXYZAnalysis(int $tenantId, int $days = 90): array
    {
        $items = $this->db->table('inventory_items')
            ->where('tenant_id', $tenantId)
            ->get();

        $abcClassification = $this->performABCClassification($items);
        $xyzClassification = $this->performXYZClassification($items, $days);

        $combinedClassification = [];

        foreach ($items as $item) {
            $abcClass = $abcClassification[$item->id] ?? 'C';
            $xyzClass = $xyzClassification[$item->id] ?? 'Z';
            $combinedClass = $abcClass . $xyzClass;

            $combinedClassification[] = [
                'inventory_item_id' => $item->id,
                'name' => $item->name,
                'sku' => $item->sku,
                'abc_class' => $abcClass,
                'xyz_class' => $xyzClass,
                'combined_class' => $combinedClass,
                'strategy' => $this->getInventoryStrategy($combinedClass),
                'value' => $item->current_stock * ($item->unit_cost ?? 0),
                'demand_variability' => $this->calculateDemandVariability($item->id, $days),
            ];
        }

        $summary = $this->generateABCXYZSummary($combinedClassification);

        $this->logAction(
            action: 'abc_xyz_analysis_completed',
            entityType: 'InventoryItem',
            entityId: null,
            context: [
                'tenant_id' => $tenantId,
                'total_items' => count($combinedClassification),
                'period_days' => $days,
            ],
            userId: 0,
            tenantId: $tenantId
        );

        return [
            'tenant_id' => $tenantId,
            'period_days' => $days,
            'classification' => $combinedClassification,
            'summary' => $summary,
        ];
    }

    /**
     * Perform ABC classification based on value
     *
     * @param  mixed  $items  Inventory items
     * @return array ABC classification
     */
    private function performABCClassification($items): array
    {
        $itemValues = $items->map(function ($item) {
            return [
                'id' => $item->id,
                'value' => $item->current_stock * ($item->unit_cost ?? 1),
            ];
        })->sortByDesc('value')->values();

        $totalValue = $itemValues->sum('value');
        $cumulativeValue = 0;
        $classification = [];

        foreach ($itemValues as $item) {
            $cumulativeValue += $item['value'];
            $cumulativePercentage = ($cumulativeValue / $totalValue) * 100;

            if ($cumulativePercentage <= 70) {
                $class = 'A';
            } elseif ($cumulativePercentage <= 90) {
                $class = 'B';
            } else {
                $class = 'C';
            }

            $classification[$item['id']] = $class;
        }

        return $classification;
    }

    /**
     * Perform XYZ classification based on demand variability
     *
     * @param  mixed  $items  Inventory items
     * @param  int  $days  Analysis period
     * @return array XYZ classification
     */
    private function performXYZClassification($items, int $days): array
    {
        $classification = [];

        foreach ($items as $item) {
            $variability = $this->calculateDemandVariability($item->id, $days);

            if ($variability < 0.15) {
                $class = 'X';
            } elseif ($variability < 0.35) {
                $class = 'Y';
            } else {
                $class = 'Z';
            }

            $classification[$item->id] = $class;
        }

        return $classification;
    }

    /**
     * Calculate demand variability coefficient of variation
     *
     * @param  int  $inventoryItemId  Inventory item ID
     * @param  int  $days  Period in days
     * @return float Coefficient of variation
     */
    private function calculateDemandVariability(int $inventoryItemId, int $days): float
    {
        $dailyDemand = $this->getDailyDemand($inventoryItemId, $days);

        if (count($dailyDemand) < 2) {
            return 1.0;
        }

        $mean = array_sum($dailyDemand) / count($dailyDemand);

        if ($mean === 0) {
            return 1.0;
        }

        $variance = array_sum(array_map(function ($x) use ($mean) {
            return pow($x - $mean, 2);
        }, $dailyDemand)) / count($dailyDemand);

        $stdDev = sqrt($variance);
        $coefficientOfVariation = $stdDev / $mean;

        return $coefficientOfVariation;
    }

    /**
     * Get daily demand for item
     *
     * @param  int  $inventoryItemId  Inventory item ID
     * @param  int  $days  Period in days
     * @return array Daily demand quantities
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

    /**
     * Get inventory strategy based on ABC-XYZ class
     *
     * @param  string  $combinedClass  Combined ABC-XYZ class
     * @return string Strategy description
     */
    private function getInventoryStrategy(string $combinedClass): string
    {
        return match ($combinedClass) {
            'AX' => 'High value, stable demand. Continuous review, JIT ordering.',
            'AY' => 'High value, variable demand. Safety stock required, regular review.',
            'AZ' => 'High value, irregular demand. Make-to-order, minimal stock.',
            'BX' => 'Medium value, stable demand. Periodic review, moderate safety stock.',
            'BY' => 'Medium value, variable demand. Periodic review, higher safety stock.',
            'BZ' => 'Medium value, irregular demand. Make-to-order or minimal stock.',
            'CX' => 'Low value, stable demand. Bulk ordering, high safety stock.',
            'CY' => 'Low value, variable demand. Bulk ordering, moderate safety stock.',
            'CZ' => 'Low value, irregular demand. Order on demand, minimal stock.',
            default => 'Standard inventory management approach.',
        };
    }

    /**
     * Generate ABC-XYZ summary statistics
     *
     * @param  array  $classification  Classification data
     * @return array Summary statistics
     */
    private function generateABCXYZSummary(array $classification): array
    {
        $summary = [];

        for ($abc = 0; $abc < 3; $abc++) {
            $abcClass = ['A', 'B', 'C'][$abc];
            $summary[$abcClass] = [];

            for ($xyz = 0; $xyz < 3; $xyz++) {
                $xyzClass = ['X', 'Y', 'Z'][$xyz];
                $combinedClass = $abcClass . $xyzClass;

                $itemsInClass = array_filter($classification, fn ($item) => $item['combined_class'] === $combinedClass);
                $totalValue = array_sum(array_column($itemsInClass, 'value'));

                $summary[$abcClass][$xyzClass] = [
                    'count' => count($itemsInClass),
                    'total_value' => $totalValue,
                    'percentage_of_total' => count($classification) > 0
                        ? (count($itemsInClass) / count($classification)) * 100
                        : 0,
                ];
            }
        }

        return $summary;
    }

    /**
     * Get items by ABC-XYZ class
     *
     * @param  int  $tenantId  Tenant ID
     * @param  string  $abcClass  ABC class (A, B, C)
     * @param  string  $xyzClass  XYZ class (X, Y, Z)
     * @return array Items in class
     */
    public function getItemsByClass(int $tenantId, string $abcClass, string $xyzClass): array
    {
        $analysis = $this->performABCXYZAnalysis($tenantId);

        return array_filter($analysis['classification'], function ($item) use ($abcClass, $xyzClass) {
            return $item['abc_class'] === $abcClass && $item['xyz_class'] === $xyzClass;
        });
    }

    /**
     * Generate ABC-XYZ matrix visualization data
     *
     * @param  int  $tenantId  Tenant ID
     * @return array Matrix data
     */
    public function generateMatrixData(int $tenantId): array
    {
        $analysis = $this->performABCXYZAnalysis($tenantId);

        $matrix = [];

        for ($abc = 0; $abc < 3; $abc++) {
            $abcClass = ['A', 'B', 'C'][$abc];
            $matrix[$abcClass] = [];

            for ($xyz = 0; $xyz < 3; $xyz++) {
                $xyzClass = ['X', 'Y', 'Z'][$xyz];
                $combinedClass = $abcClass . $xyzClass;

                $itemsInClass = array_filter($analysis['classification'], fn ($item) => $item['combined_class'] === $combinedClass);
                $totalValue = array_sum(array_column($itemsInClass, 'value'));

                $matrix[$abcClass][$xyzClass] = [
                    'class' => $combinedClass,
                    'count' => count($itemsInClass),
                    'total_value' => $totalValue,
                    'strategy' => $this->getInventoryStrategy($combinedClass),
                ];
            }
        }

        return [
            'tenant_id' => $tenantId,
            'matrix' => $matrix,
        ];
    }

    /**
     * Export ABC-XYZ analysis to CSV
     *
     * @param  int  $tenantId  Tenant ID
     * @return string CSV content
     */
    public function exportToCsv(int $tenantId): string
    {
        $analysis = $this->performABCXYZAnalysis($tenantId);

        $lines = [];
        $lines[] = 'Item ID,SKU,Name,ABC Class,XYZ Class,Combined Class,Strategy,Value,Demand Variability';

        foreach ($analysis['classification'] as $item) {
            $lines[] = sprintf(
                '%s,%s,%s,%s,%s,%s,%s,%s,%s',
                $item['inventory_item_id'],
                $item['sku'],
                $item['name'],
                $item['abc_class'],
                $item['xyz_class'],
                $item['combined_class'],
                $item['strategy'],
                number_format($item['value'], 2),
                number_format($item['demand_variability'], 4)
            );
        }

        return implode("\n", $lines);
    }
}
