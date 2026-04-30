<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;
use Illuminate\Database\DatabaseManager;
use Illuminate\Cache\Repository as CacheRepository;
use Psr\Log\LoggerInterface;

/**
 * Variance Thresholds Service
 *
 * Manages variance thresholds for inventory operations.
 * Defines acceptable tolerance levels for count discrepancies.
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class VarianceThresholdsService
{
    use WithAuditLogging;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService,
        private readonly CacheRepository $cache
    ) {}

    /**
     * Get variance threshold for item
     *
     * @param  int  $inventoryItemId  Inventory item ID
     * @return float Variance threshold percentage
     */
    public function getVarianceThreshold(int $inventoryItemId): float
    {
        $item = $this->db->table('inventory_items')
            ->where('id', $inventoryItemId)
            ->first();

        if (! $item) {
            return 5.0;
        }

        if ($item->variance_threshold) {
            return (float) $item->variance_threshold;
        }

        $tenantThreshold = $this->db->table('variance_thresholds')
            ->where('tenant_id', $item->tenant_id)
            ->where('is_default', true)
            ->value('threshold');

        return (float) ($tenantThreshold ?? 5.0);
    }

    /**
     * Set variance threshold for item
     *
     * @param  int  $inventoryItemId  Inventory item ID
     * @param  float  $threshold  Threshold percentage
     * @param  int  $userId  User setting threshold
     * @return bool
     */
    public function setVarianceThreshold(int $inventoryItemId, float $threshold, int $userId): bool
    {
        $item = $this->db->table('inventory_items')
            ->where('id', $inventoryItemId)
            ->first();

        if (! $item) {
            throw new \RuntimeException("Inventory item not found: {$inventoryItemId}");
        }

        $this->db->table('inventory_items')
            ->where('id', $inventoryItemId)
            ->update([
                'variance_threshold' => $threshold,
                'updated_at' => now(),
            ]);

        $this->logAction(
            action: 'variance_threshold_set',
            entityType: 'InventoryItem',
            entityId: $inventoryItemId,
            context: [
                'threshold' => $threshold,
            ],
            userId: $userId,
            tenantId: $item->tenant_id
        );

        $this->cache->tags(['inventory', "item:{$inventoryItemId}"])->flush();

        return true;
    }

    /**
     * Set default variance threshold for tenant
     *
     * @param  int  $tenantId  Tenant ID
     * @param  float  $threshold  Threshold percentage
     * @param  int  $userId  User setting threshold
     * @return bool
     */
    public function setDefaultVarianceThreshold(int $tenantId, float $threshold, int $userId): bool
    {
        $existing = $this->db->table('variance_thresholds')
            ->where('tenant_id', $tenantId)
            ->where('is_default', true)
            ->first();

        if ($existing) {
            $this->db->table('variance_thresholds')
                ->where('id', $existing->id)
                ->update([
                    'threshold' => $threshold,
                    'updated_by' => $userId,
                    'updated_at' => now(),
                ]);
        } else {
            $this->db->table('variance_thresholds')->insert([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'tenant_id' => $tenantId,
                'threshold' => $threshold,
                'is_default' => true,
                'created_by' => $userId,
                'created_at' => now(),
            ]);
        }

        $this->logAction(
            action: 'default_variance_threshold_set',
            entityType: 'VarianceThreshold',
            entityId: $existing->id ?? null,
            context: [
                'threshold' => $threshold,
            ],
            userId: $userId,
            tenantId: $tenantId
        );

        $this->cache->tags(['inventory'])->flush();

        return true;
    }

    /**
     * Set category-based variance threshold
     *
     * @param  int  $tenantId  Tenant ID
     * @param  string  $category  Item category
     * @param  float  $threshold  Threshold percentage
     * @param  int  $userId  User setting threshold
     * @return bool
     */
    public function setCategoryVarianceThreshold(int $tenantId, string $category, float $threshold, int $userId): bool
    {
        $existing = $this->db->table('variance_thresholds')
            ->where('tenant_id', $tenantId)
            ->where('category', $category)
            ->first();

        if ($existing) {
            $this->db->table('variance_thresholds')
                ->where('id', $existing->id)
                ->update([
                    'threshold' => $threshold,
                    'updated_by' => $userId,
                    'updated_at' => now(),
                ]);
        } else {
            $this->db->table('variance_thresholds')->insert([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'tenant_id' => $tenantId,
                'category' => $category,
                'threshold' => $threshold,
                'is_default' => false,
                'created_by' => $userId,
                'created_at' => now(),
            ]);
        }

        $this->logAction(
            action: 'category_variance_threshold_set',
            entityType: 'VarianceThreshold',
            entityId: $existing->id ?? null,
            context: [
                'category' => $category,
                'threshold' => $threshold,
            ],
            userId: $userId,
            tenantId: $tenantId
        );

        $this->cache->tags(['inventory'])->flush();

        return true;
    }

    /**
     * Check if variance exceeds threshold
     *
     * @param  int  $inventoryItemId  Inventory item ID
     * @param  float  $variancePercentage  Variance percentage
     * @return bool Exceeds threshold
     */
    public function exceedsThreshold(int $inventoryItemId, float $variancePercentage): bool
    {
        $threshold = $this->getVarianceThreshold($inventoryItemId);

        return abs($variancePercentage) > $threshold;
    }

    /**
     * Get variance alert level
     *
     * @param  int  $inventoryItemId  Inventory item ID
     * @param  float  $variancePercentage  Variance percentage
     * @return string Alert level (info, warning, critical)
     */
    public function getVarianceAlertLevel(int $inventoryItemId, float $variancePercentage): string
    {
        $threshold = $this->getVarianceThreshold($inventoryItemId);
        $absoluteVariance = abs($variancePercentage);

        if ($absoluteVariance < $threshold) {
            return 'info';
        }

        if ($absoluteVariance < $threshold * 2) {
            return 'warning';
        }

        return 'critical';
    }

    /**
     * Get items exceeding variance threshold
     *
     * @param  string  $planId  Cycle count plan ID
     * @return array Items exceeding threshold
     */
    public function getItemsExceedingThreshold(string $planId): array
    {
        $items = $this->db->table('cycle_count_items')
            ->where('plan_id', $planId)
            ->where('status', 'counted')
            ->get();

        $exceedingItems = [];

        foreach ($items as $item) {
            $threshold = $this->getVarianceThreshold($item->inventory_item_id);

            if (abs($item->variance_percentage) > $threshold) {
                $inventoryItem = $this->db->table('inventory_items')
                    ->where('id', $item->inventory_item_id)
                    ->first();

                $exceedingItems[] = [
                    'inventory_item_id' => $item->inventory_item_id,
                    'name' => $inventoryItem->name ?? null,
                    'sku' => $inventoryItem->sku ?? null,
                    'expected' => $item->expected_quantity,
                    'actual' => $item->actual_quantity,
                    'discrepancy' => $item->discrepancy,
                    'variance_percentage' => $item->variance_percentage,
                    'threshold' => $threshold,
                    'alert_level' => $this->getVarianceAlertLevel($item->inventory_item_id, $item->variance_percentage),
                ];
            }
        }

        return $exceedingItems;
    }

    /**
     * Get recommended threshold based on ABC class
     *
     * @param  string  $abcClass  ABC class (A, B, C)
     * @return float Recommended threshold
     */
    public function getRecommendedThreshold(string $abcClass): float
    {
        return match (strtoupper($abcClass)) {
            'A' => 1.0,
            'B' => 2.0,
            'C' => 5.0,
            default => 5.0,
        };
    }

    /**
     * Get variance threshold statistics
     *
     * @param  int  $tenantId  Tenant ID
     * @return array Statistics
     */
    public function getThresholdStatistics(int $tenantId): array
    {
        $thresholds = $this->db->table('variance_thresholds')
            ->where('tenant_id', $tenantId)
            ->get();

        $defaultThreshold = $thresholds->where('is_default', true)->first();
        $categoryThresholds = $thresholds->where('is_default', false);

        return [
            'default_threshold' => $defaultThreshold ? (float) $defaultThreshold->threshold : 5.0,
            'category_thresholds' => $categoryThresholds->map(function ($threshold) {
                return [
                    'category' => $threshold->category,
                    'threshold' => (float) $threshold->threshold,
                ];
            })->toArray(),
            'total_categories' => $categoryThresholds->count(),
        ];
    }

    /**
     * Auto-adjust threshold based on historical data
     *
     * @param  int  $inventoryItemId  Inventory item ID
     * @param  int  $days  Number of days to analyze
     * @return array Adjustment recommendation
     */
    public function getThresholdAdjustmentRecommendation(int $inventoryItemId, int $days = 90): array
    {
        $startDate = now()->subDays($days);

        $countItems = $this->db->table('cycle_count_items as cci')
            ->join('cycle_count_plans as ccp', 'cci.plan_id', '=', 'ccp.id')
            ->where('cci.inventory_item_id', $inventoryItemId)
            ->where('ccp.created_at', '>=', $startDate)
            ->where('cci.status', 'approved')
            ->select('cci.variance_percentage')
            ->get();

        if ($countItems->isEmpty()) {
            return [
                'recommendation' => 'no_data',
                'current_threshold' => $this->getVarianceThreshold($inventoryItemId),
                'reason' => 'Insufficient historical data',
            ];
        }

        $variances = $countItems->pluck('variance_percentage')->map(fn ($v) => abs($v))->values();
        $averageVariance = $variances->avg();
        $maxVariance = $variances->max();
        $p90Variance = $this->calculatePercentile($variances->toArray(), 90);

        $currentThreshold = $this->getVarianceThreshold($inventoryItemId);
        $recommendedThreshold = round($p90Variance * 1.2, 2);

        $recommendation = 'no_change';

        if ($p90Variance > $currentThreshold * 1.5) {
            $recommendation = 'increase';
        } elseif ($p90Variance < $currentThreshold * 0.5) {
            $recommendation = 'decrease';
        }

        return [
            'recommendation' => $recommendation,
            'current_threshold' => $currentThreshold,
            'recommended_threshold' => $recommendedThreshold,
            'average_variance' => $averageVariance,
            'max_variance' => $maxVariance,
            'p90_variance' => $p90Variance,
            'data_points' => $countItems->count(),
        ];
    }

    /**
     * Calculate percentile from array
     *
     * @param  array  $values  Values
     * @param  int  $percentile  Percentile (0-100)
     * @return float Percentile value
     */
    private function calculatePercentile(array $values, int $percentile): float
    {
        if (empty($values)) {
            return 0.0;
        }

        sort($values);
        $index = ($percentile / 100) * (count($values) - 1);

        if (floor($index) == $index) {
            return $values[(int) $index];
        }

        $lower = $values[(int) floor($index)];
        $upper = $values[(int) ceil($index)];

        return $lower + ($upper - $lower) * ($index - floor($index));
    }
}
