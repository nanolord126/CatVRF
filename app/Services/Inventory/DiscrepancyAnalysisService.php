<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;
use Illuminate\Database\DatabaseManager;
use Illuminate\Cache\Repository as CacheRepository;
use Psr\Log\LoggerInterface;

/**
 * Discrepancy Analysis Service
 *
 * Analyzes inventory count discrepancies and provides insights for process improvement.
 * Identifies patterns in discrepancies to prevent future inventory errors.
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class DiscrepancyAnalysisService
{
    use WithAuditLogging;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService,
        private readonly CacheRepository $cache
    ) {}

    /**
     * Analyze discrepancies for a cycle count plan
     *
     * @param  string  $planId  Cycle count plan ID
     * @return array Analysis results
     */
    public function analyzePlanDiscrepancies(string $planId): array
    {
        $items = $this->db->table('cycle_count_items')
            ->where('plan_id', $planId)
            ->get();

        $totalItems = $items->count();
        $itemsWithDiscrepancy = $items->where('discrepancy', '!=', 0)->count();
        $totalQuantityDiscrepancy = $items->sum('discrepancy');
        $totalValueDiscrepancy = 0;

        $discrepancyByCategory = [];
        $discrepancyByLocation = [];
        $discrepancyByReason = [];

        foreach ($items as $item) {
            if ($item->discrepancy !== 0) {
                $inventoryItem = $this->db->table('inventory_items')
                    ->where('id', $item->inventory_item_id)
                    ->first();

                if ($inventoryItem) {
                    $itemValue = abs($item->discrepancy) * ($inventoryItem->unit_cost ?? 0);
                    $totalValueDiscrepancy += $itemValue;

                    $category = $inventoryItem->category ?? 'unknown';
                    $location = $inventoryItem->location ?? 'unknown';

                    $discrepancyByCategory[$category] = ($discrepancyByCategory[$category] ?? 0) + $itemValue;
                    $discrepancyByLocation[$location] = ($discrepancyByLocation[$location] ?? 0) + $itemValue;
                }

                $reason = $item->notes ?? 'unspecified';
                $discrepancyByReason[$reason] = ($discrepancyByReason[$reason] ?? 0) + 1;
            }
        }

        $varianceData = $items->pluck('variance_percentage')->filter()->values();
        $averageVariance = $varianceData->avg() ?? 0;
        $maxVariance = $varianceData->max() ?? 0;

        return [
            'summary' => [
                'total_items' => $totalItems,
                'items_with_discrepancy' => $itemsWithDiscrepancy,
                'discrepancy_rate' => $totalItems > 0 ? ($itemsWithDiscrepancy / $totalItems) * 100 : 0,
                'total_quantity_discrepancy' => $totalQuantityDiscrepancy,
                'total_value_discrepancy' => $totalValueDiscrepancy,
                'average_variance' => $averageVariance,
                'max_variance' => $maxVariance,
            ],
            'breakdown' => [
                'by_category' => $discrepancyByCategory,
                'by_location' => $discrepancyByLocation,
                'by_reason' => $discrepancyByReason,
            ],
            'items' => $items->where('discrepancy', '!=', 0)->map(function ($item) {
                return [
                    'inventory_item_id' => $item->inventory_item_id,
                    'expected' => $item->expected_quantity,
                    'actual' => $item->actual_quantity,
                    'discrepancy' => $item->discrepancy,
                    'variance_percentage' => $item->variance_percentage,
                ];
            })->toArray(),
        ];
    }

    /**
     * Get discrepancy trends over time
     *
     * @param  int  $warehouseId  Warehouse ID
     * @param  int  $days  Number of days to analyze
     * @return array Trend data
     */
    public function getDiscrepancyTrends(int $warehouseId, int $days = 90): array
    {
        $startDate = now()->subDays($days);

        $plans = $this->db->table('cycle_count_plans')
            ->where('warehouse_id', $warehouseId)
            ->where('created_at', '>=', $startDate)
            ->where('status', 'completed')
            ->get();

        $trends = [];
        $dailyDiscrepancyRates = [];

        foreach ($plans as $plan) {
            $items = $this->db->table('cycle_count_items')
                ->where('plan_id', $plan->id)
                ->get();

            $totalItems = $items->count();
            $itemsWithDiscrepancy = $items->where('discrepancy', '!=', 0)->count();
            $discrepancyRate = $totalItems > 0 ? ($itemsWithDiscrepancy / $totalItems) * 100 : 0;

            $date = $plan->created_at->toDateString();
            $dailyDiscrepancyRates[$date] = ($dailyDiscrepancyRates[$date] ?? []) + [$discrepancyRate];
        }

        foreach ($dailyDiscrepancyRates as $date => $rates) {
            $trends[] = [
                'date' => $date,
                'average_discrepancy_rate' => array_sum($rates) / count($rates),
                'count_counted' => count($rates),
            ];
        }

        usort($trends, fn ($a, $b) => strtotime($a['date']) - strtotime($b['date']));

        $overallTrend = $this->calculateTrendDirection(array_column($trends, 'average_discrepancy_rate'));

        return [
            'trends' => $trends,
            'overall_trend' => $overallTrend,
            'average_discrepancy_rate' => count($trends) > 0
                ? array_sum(array_column($trends, 'average_discrepancy_rate')) / count($trends)
                : 0,
        ];
    }

    /**
     * Identify high discrepancy items
     *
     * @param  int  $warehouseId  Warehouse ID
     * @param  int  $threshold  Discrepancy count threshold
     * @return array High discrepancy items
     */
    public function identifyHighDiscrepancyItems(int $warehouseId, int $threshold = 3): array
    {
        $itemDiscrepancies = $this->db->table('cycle_count_items as cci')
            ->join('cycle_count_plans as ccp', 'cci.plan_id', '=', 'ccp.id')
            ->where('ccp.warehouse_id', $warehouseId)
            ->where('cci.discrepancy', '!=', 0)
            ->selectRaw('cci.inventory_item_id, COUNT(*) as discrepancy_count, AVG(ABS(cci.discrepancy)) as avg_discrepancy')
            ->groupBy('cci.inventory_item_id')
            ->having('discrepancy_count', '>=', $threshold)
            ->orderByDesc('discrepancy_count')
            ->get();

        $highDiscrepancyItems = [];

        foreach ($itemDiscrepancies as $item) {
            $inventoryItem = $this->db->table('inventory_items')
                ->where('id', $item->inventory_item_id)
                ->first();

            if ($inventoryItem) {
                $highDiscrepancyItems[] = [
                    'inventory_item_id' => $item->inventory_item_id,
                    'name' => $inventoryItem->name,
                    'sku' => $inventoryItem->sku,
                    'discrepancy_count' => $item->discrepancy_count,
                    'average_discrepancy' => $item->avg_discrepancy,
                ];
            }
        }

        return $highDiscrepancyItems;
    }

    /**
     * Get discrepancy root cause analysis
     *
     * @param  string  $planId  Cycle count plan ID
     * @return array Root cause analysis
     */
    public function getRootCauseAnalysis(string $planId): array
    {
        $items = $this->db->table('cycle_count_items')
            ->where('plan_id', $planId)
            ->where('discrepancy', '!=', 0)
            ->get();

        $rootCauses = [
            'data_entry_errors' => 0,
            'theft_loss' => 0,
            'supplier_issues' => 0,
            'counting_errors' => 0,
            'system_errors' => 0,
            'other' => 0,
        ];

        foreach ($items as $item) {
            $notes = strtolower($item->notes ?? '');

            if (str_contains($notes, 'typo') || str_contains($notes, 'entry') || str_contains($notes, 'input')) {
                $rootCauses['data_entry_errors']++;
            } elseif (str_contains($notes, 'theft') || str_contains($notes, 'stolen') || str_contains($notes, 'missing')) {
                $rootCauses['theft_loss']++;
            } elseif (str_contains($notes, 'supplier') || str_contains($notes, 'vendor') || str_contains($notes, 'shortage')) {
                $rootCauses['supplier_issues']++;
            } elseif (str_contains($notes, 'count') || str_contains($notes, ' recount') || str_contains($notes, 'miscount')) {
                $rootCauses['counting_errors']++;
            } elseif (str_contains($notes, 'system') || str_contains($notes, 'bug') || str_contains($notes, 'glitch')) {
                $rootCauses['system_errors']++;
            } else {
                $rootCauses['other']++;
            }
        }

        $total = array_sum($rootCauses);
        $percentages = [];

        foreach ($rootCauses as $cause => $count) {
            $percentages[$cause] = $total > 0 ? ($count / $total) * 100 : 0;
        }

        return [
            'counts' => $rootCauses,
            'percentages' => $percentages,
            'primary_cause' => array_key_first($rootCauses) !== null
                ? array_keys($rootCauses, max($rootCauses))[0]
                : null,
        ];
    }

    /**
     * Calculate trend direction from data points
     *
     * @param  array  $data  Data points
     * @return string Trend direction (improving, worsening, stable)
     */
    private function calculateTrendDirection(array $data): string
    {
        if (count($data) < 2) {
            return 'stable';
        }

        $firstHalf = array_slice($data, 0, (int) (count($data) / 2));
        $secondHalf = array_slice($data, (int) (count($data) / 2));

        $firstAvg = array_sum($firstHalf) / count($firstHalf);
        $secondAvg = array_sum($secondHalf) / count($secondHalf);

        $difference = $secondAvg - $firstAvg;
        $percentChange = $firstAvg > 0 ? ($difference / $firstAvg) * 100 : 0;

        if (abs($percentChange) < 5) {
            return 'stable';
        }

        return $percentChange < 0 ? 'improving' : 'worsening';
    }

    /**
     * Generate discrepancy report for export
     *
     * @param  string  $planId  Cycle count plan ID
     * @return array Report data
     */
    public function generateDiscrepancyReport(string $planId): array
    {
        $analysis = $this->analyzePlanDiscrepancies($planId);
        $rootCause = $this->getRootCauseAnalysis($planId);

        $plan = $this->db->table('cycle_count_plans')
            ->where('id', $planId)
            ->first();

        return [
            'plan_id' => $planId,
            'warehouse_id' => $plan->warehouse_id ?? null,
            'count_type' => $plan->count_type ?? null,
            'generated_at' => now()->toIso8601String(),
            'analysis' => $analysis,
            'root_cause_analysis' => $rootCause,
            'recommendations' => $this->generateRecommendations($analysis, $rootCause),
        ];
    }

    /**
     * Generate recommendations based on analysis
     *
     * @param  array  $analysis  Discrepancy analysis
     * @param  array  $rootCause  Root cause analysis
     * @return array Recommendations
     */
    private function generateRecommendations(array $analysis, array $rootCause): array
    {
        $recommendations = [];

        if ($analysis['summary']['discrepancy_rate'] > 10) {
            $recommendations[] = 'High discrepancy rate detected. Consider increasing cycle count frequency.';
        }

        if ($rootCause['primary_cause'] === 'counting_errors') {
            $recommendations[] = 'Implement additional training for counting staff.';
            $recommendations[] = 'Consider using barcode scanners for counting.';
        }

        if ($rootCause['primary_cause'] === 'data_entry_errors') {
            $recommendations[] = 'Review data entry procedures and implement validation checks.';
        }

        if ($rootCause['primary_cause'] === 'theft_loss') {
            $recommendations[] = 'Review security measures and access controls.';
            $recommendations[] = 'Implement more frequent counts for high-value items.';
        }

        if ($analysis['summary']['average_variance'] > 5) {
            $recommendations[] = 'Average variance exceeds acceptable threshold. Review counting accuracy.';
        }

        return $recommendations;
    }
}
