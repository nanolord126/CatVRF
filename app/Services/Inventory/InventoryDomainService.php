<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;
use Illuminate\Database\DatabaseManager;
use Illuminate\Cache\Repository as CacheRepository;
use Psr\Log\LoggerInterface;

/**
 * Inventory Domain Service - Extended WMS Logic
 *
 * Implements advanced inventory management features:
 * - Reorder points calculation
 * - Safety stock management
 * - ABC analysis (classification by importance)
 * - FEFO (First Expired First Out) for medical products
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class InventoryDomainService
{
    use WithAuditLogging;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService,
        private readonly CacheRepository $cache
    ) {}

    /**
     * Calculate optimal reorder point for product
     *
     * @param  int  $inventoryItemId  Inventory item ID
     * @param  int  $leadTimeDays  Lead time in days
     * @param  float  $serviceLevel  Service level (0.9 = 90%)
     * @return int Reorder point
     */
    public function calculateReorderPoint(
        int $inventoryItemId,
        int $leadTimeDays,
        float $serviceLevel = 0.95
    ): int {
        $usageData = $this->getDailyUsage($inventoryItemId, 90);

        if (empty($usageData)) {
            return 0;
        }

        $avgDailyUsage = array_sum($usageData) / count($usageData);
        $stdDev = $this->calculateStandardDeviation($usageData);

        $safetyStock = $this->calculateSafetyStock($stdDev, $leadTimeDays, $serviceLevel);
        $reorderPoint = (int) ceil(($avgDailyUsage * $leadTimeDays) + $safetyStock);

        $this->db->table('inventory_items')
            ->where('id', $inventoryItemId)
            ->update([
                'min_stock_threshold' => $reorderPoint,
                'updated_at' => now(),
            ]);

        $this->cache->tags(['inventory'])->flush();

        return $reorderPoint;
    }

    /**
     * Calculate safety stock level
     *
     * @param  float  $stdDev  Standard deviation of daily usage
     * @param  int  $leadTimeDays  Lead time in days
     * @param  float  $serviceLevel  Service level
     * @return float Safety stock quantity
     */
    public function calculateSafetyStock(
        float $stdDev,
        int $leadTimeDays,
        float $serviceLevel
    ): float {
        $zScore = $this->getZScore($serviceLevel);
        $leadTimeStdDev = $stdDev * sqrt($leadTimeDays);

        return $zScore * $leadTimeStdDev;
    }

    /**
     * Perform ABC analysis on inventory
     *
     * Classifies items into:
     * - A: High value, high turnover (70% of value, 10% of items)
     * - B: Medium value, medium turnover (20% of value, 20% of items)
     * - C: Low value, low turnover (10% of value, 70% of items)
     *
     * @param  int  $tenantId  Tenant ID
     * @return array ABC classification results
     */
    public function performABCAnalysis(int $tenantId): array
    {
        $items = $this->db->table('inventory_items')
            ->where('tenant_id', $tenantId)
            ->get();

        $totalValue = $items->sum(function ($item) {
            return $item->current_stock * ($item->unit_cost ?? 1);
        });

        $itemValues = $items->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'value' => $item->current_stock * ($item->unit_cost ?? 1),
                'turnover' => $this->getAnnualTurnover($item->id),
            ];
        })->sortByDesc('value')->values();

        $cumulativeValue = 0;
        $classified = [];

        foreach ($itemValues as $index => $item) {
            $cumulativeValue += $item['value'];
            $cumulativePercentage = ($cumulativeValue / $totalValue) * 100;

            if ($cumulativePercentage <= 70) {
                $class = 'A';
            } elseif ($cumulativePercentage <= 90) {
                $class = 'B';
            } else {
                $class = 'C';
            }

            $classified[] = array_merge($item, [
                'abc_class' => $class,
                'cumulative_percentage' => $cumulativePercentage,
            ]);

            $this->db->table('inventory_items')
                ->where('id', $item['id'])
                ->update(['abc_class' => $class]);
        }

        $this->logAction(
            action: 'abc_analysis_completed',
            entityType: 'InventoryItem',
            entityId: null,
            context: [
                'tenant_id' => $tenantId,
                'total_items' => $items->count(),
                'total_value' => $totalValue,
            ],
            userId: 0,
            tenantId: $tenantId
        );

        return [
            'total_items' => $items->count(),
            'total_value' => $totalValue,
            'classification' => $classified,
            'summary' => [
                'A' => count(array_filter($classified, fn ($i) => $i['abc_class'] === 'A')),
                'B' => count(array_filter($classified, fn ($i) => $i['abc_class'] === 'B')),
                'C' => count(array_filter($classified, fn ($i) => $i['abc_class'] === 'C')),
            ],
        ];
    }

    /**
     * Get items requiring reorder (below reorder point)
     *
     * @param  int  $tenantId  Tenant ID
     * @return array Items to reorder
     */
    public function getReorderRecommendations(int $tenantId): array
    {
        return $this->db->table('inventory_items')
            ->where('tenant_id', $tenantId)
            ->whereRaw('current_stock <= min_stock_threshold')
            ->get()
            ->map(function ($item) {
                $suggestedOrderQty = ($item->max_stock_threshold ?? $item->min_stock_threshold * 2) - $item->current_stock;

                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'sku' => $item->sku,
                    'current_stock' => $item->current_stock,
                    'min_stock_threshold' => $item->min_stock_threshold,
                    'suggested_order_qty' => max(0, $suggestedOrderQty),
                    'priority' => $this->calculateReorderPriority($item),
                ];
            })
            ->sortByDesc('priority')
            ->values()
            ->toArray();
    }

    /**
     * Get FEFO (First Expired First Out) picking order for medical products
     *
     * @param  int  $productId  Product ID
     * @param  int  $quantity  Quantity needed
     * @return array Batches to pick (ordered by expiry date)
     */
    public function getFEFOPickingOrder(int $productId, int $quantity): array
    {
        $batches = $this->db->table('inventory_batches')
            ->where('product_id', $productId)
            ->where('quantity', '>', 0)
            ->where('expiry_date', '>', now())
            ->orderBy('expiry_date', 'asc')
            ->get();

        $pickingOrder = [];
        $remainingQty = $quantity;

        foreach ($batches as $batch) {
            if ($remainingQty <= 0) {
                break;
            }

            $pickQty = min($batch->quantity, $remainingQty);
            $pickingOrder[] = [
                'batch_id' => $batch->id,
                'batch_number' => $batch->batch_number,
                'expiry_date' => $batch->expiry_date,
                'quantity' => $pickQty,
                'remaining' => $batch->quantity - $pickQty,
            ];

            $remainingQty -= $pickQty;
        }

        if ($remainingQty > 0) {
            $available = $quantity - $remainingQty;
            $this->logger->warning('Insufficient stock for FEFO picking', [
                'product_id' => $productId,
                'needed' => $quantity,
                'available' => $available,
            ]);
            throw new \RuntimeException("Insufficient stock for FEFO picking. Need {$quantity}, available: {$available}");
        }

        $this->logger->info('FEFO picking order generated', [
            'product_id' => $productId,
            'quantity' => $quantity,
            'batches_count' => count($pickingOrder),
        ]);

        return $pickingOrder;
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
     * Get Z-score for service level
     *
     * @param  float  $serviceLevel  Service level (0.0-1.0)
     * @return float Z-score
     */
    private function getZScore(float $serviceLevel): float
    {
        $zScores = [
            0.90 => 1.28,
            0.95 => 1.65,
            0.99 => 2.33,
        ];

        return $zScores[$serviceLevel] ?? 1.65;
    }

    /**
     * Get annual turnover for item
     *
     * @param  int  $inventoryItemId  Inventory item ID
     * @return float Annual turnover
     */
    private function getAnnualTurnover(int $inventoryItemId): float
    {
        $totalSold = $this->db->table('stock_movements')
            ->where('inventory_item_id', $inventoryItemId)
            ->where('type', 'out')
            ->where('created_at', '>=', now()->subYear())
            ->selectRaw('SUM(ABS(quantity)) as total')
            ->value('total');

        return (float) ($totalSold ?? 0);
    }

    /**
     * Calculate reorder priority (higher = more urgent)
     *
     * @param  mixed  $item  Inventory item
     * @return int Priority score (1-10)
     */
    private function calculateReorderPriority($item): int
    {
        $stockRatio = $item->current_stock / max(1, $item->min_stock_threshold);

        if ($stockRatio <= 0) {
            return 10;
        }

        if ($stockRatio <= 0.25) {
            return 9;
        }

        if ($stockRatio <= 0.5) {
            return 7;
        }

        if ($stockRatio <= 0.75) {
            return 5;
        }

        return 3;
    }
}
