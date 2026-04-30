<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;
use Illuminate\Database\DatabaseManager;
use Illuminate\Cache\Repository as CacheRepository;
use Psr\Log\LoggerInterface;

/**
 * Expiration Report Service
 *
 * Generates reports for expired and expiring inventory items:
 * - Already expired items
 * - Items expiring soon
 * - Shelf life analysis
 * - Expiration by category
 * - Write-off recommendations
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class ExpirationReportService
{
    use WithAuditLogging;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService,
        private readonly CacheRepository $cache
    ) {}

    /**
     * Generate expiration report for warehouse
     *
     * @param  int  $warehouseId  Warehouse ID
     * @return array Expiration report
     */
    public function generateExpirationReport(int $warehouseId): array
    {
        $batches = $this->db->table('inventory_batches')
            ->where('warehouse_id', $warehouseId)
            ->where('status', '!=', 'recalled')
            ->get();

        $now = now();
        $expired = [];
        $expiring7Days = [];
        $expiring30Days = [];
        $expiring90Days = [];
        $valid = [];

        foreach ($batches as $batch) {
            $expiryDate = \Carbon\Carbon::parse($batch->expiry_date);
            $daysUntilExpiry = $now->diffInDays($expiryDate, false);

            $batchData = [
                'batch_id' => $batch->id,
                'batch_number' => $batch->batch_number,
                'product_id' => $batch->product_id,
                'quantity' => $batch->quantity,
                'current_quantity' => $batch->current_quantity,
                'expiry_date' => $batch->expiry_date,
                'days_until_expiry' => $daysUntilExpiry,
                'status' => $batch->status,
            ];

            if ($daysUntilExpiry < 0) {
                $expired[] = $batchData;
            } elseif ($daysUntilExpiry <= 7) {
                $expiring7Days[] = $batchData;
            } elseif ($daysUntilExpiry <= 30) {
                $expiring30Days[] = $batchData;
            } elseif ($daysUntilExpiry <= 90) {
                $expiring90Days[] = $batchData;
            } else {
                $valid[] = $batchData;
            }
        }

        $totalExpiredValue = $this->calculateBatchValue($expired);
        $totalExpiring7DaysValue = $this->calculateBatchValue($expiring7Days);
        $totalExpiring30DaysValue = $this->calculateBatchValue($expiring30Days);
        $totalExpiring90DaysValue = $this->calculateBatchValue($expiring90Days);

        return [
            'warehouse_id' => $warehouseId,
            'generated_at' => $now->toIso8601String(),
            'summary' => [
                'total_batches' => count($batches),
                'expired_count' => count($expired),
                'expired_value' => $totalExpiredValue,
                'expiring_7_days_count' => count($expiring7Days),
                'expiring_7_days_value' => $totalExpiring7DaysValue,
                'expiring_30_days_count' => count($expiring30Days),
                'expiring_30_days_value' => $totalExpiring30DaysValue,
                'expiring_90_days_count' => count($expiring90Days),
                'expiring_90_days_value' => $totalExpiring90DaysValue,
            ],
            'expired' => $expired,
            'expiring_7_days' => $expiring7Days,
            'expiring_30_days' => $expiring30Days,
            'expiring_90_days' => $expiring90Days,
        ];
    }

    /**
     * Generate expiration report by category
     *
     * @param  int  $warehouseId  Warehouse ID
     * @return array Category expiration data
     */
    public function generateCategoryExpirationReport(int $warehouseId): array
    {
        $batches = $this->db->table('inventory_batches as ib')
            ->join('products as p', 'ib.product_id', '=', 'p.id')
            ->where('ib.warehouse_id', $warehouseId)
            ->where('ib.status', '!=', 'recalled')
            ->select('ib.*', 'p.category')
            ->get();

        $categoryData = [];

        foreach ($batches as $batch) {
            $category = $batch->category ?? 'uncategorized';
            $expiryDate = \Carbon\Carbon::parse($batch->expiry_date);
            $daysUntilExpiry = now()->diffInDays($expiryDate, false);

            if (! isset($categoryData[$category])) {
                $categoryData[$category] = [
                    'category' => $category,
                    'total_batches' => 0,
                    'expired_count' => 0,
                    'expired_value' => 0,
                    'expiring_30_days_count' => 0,
                    'expiring_30_days_value' => 0,
                ];
            }

            $categoryData[$category]['total_batches']++;

            $product = $this->db->table('products')
                ->where('id', $batch->product_id)
                ->first();
            $unitCost = $product->unit_cost ?? 0;
            $batchValue = $batch->current_quantity * $unitCost;

            if ($daysUntilExpiry < 0) {
                $categoryData[$category]['expired_count']++;
                $categoryData[$category]['expired_value'] += $batchValue;
            } elseif ($daysUntilExpiry <= 30) {
                $categoryData[$category]['expiring_30_days_count']++;
                $categoryData[$category]['expiring_30_days_value'] += $batchValue;
            }
        }

        return [
            'warehouse_id' => $warehouseId,
            'categories' => array_values($categoryData),
        ];
    }

    /**
     * Generate write-off recommendations
     *
     * @param  int  $warehouseId  Warehouse ID
     * @return array Recommendations
     */
    public function generateWriteOffRecommendations(int $warehouseId): array
    {
        $batches = $this->db->table('inventory_batches')
            ->where('warehouse_id', $warehouseId)
            ->where('status', 'available')
            ->get();

        $recommendations = [];
        $totalWriteOffValue = 0;

        foreach ($batches as $batch) {
            $expiryDate = \Carbon\Carbon::parse($batch->expiry_date);
            $daysUntilExpiry = now()->diffInDays($expiryDate, false);

            if ($daysUntilExpiry < 0) {
                $product = $this->db->table('products')
                    ->where('id', $batch->product_id)
                    ->first();
                $unitCost = $product->unit_cost ?? 0;
                $batchValue = $batch->current_quantity * $unitCost;

                $recommendations[] = [
                    'batch_id' => $batch->id,
                    'batch_number' => $batch->batch_number,
                    'product_id' => $batch->product_id,
                    'quantity' => $batch->current_quantity,
                    'expiry_date' => $batch->expiry_date,
                    'days_overdue' => abs($daysUntilExpiry),
                    'value' => $batchValue,
                    'action' => 'write_off',
                    'reason' => 'Expired',
                    'priority' => 'critical',
                ];

                $totalWriteOffValue += $batchValue;
            } elseif ($daysUntilExpiry <= 7) {
                $product = $this->db->table('products')
                    ->where('id', $batch->product_id)
                    ->first();
                $unitCost = $product->unit_cost ?? 0;
                $batchValue = $batch->current_quantity * $unitCost;

                $recommendations[] = [
                    'batch_id' => $batch->id,
                    'batch_number' => $batch->batch_number,
                    'product_id' => $batch->product_id,
                    'quantity' => $batch->current_quantity,
                    'expiry_date' => $batch->expiry_date,
                    'days_until_expiry' => $daysUntilExpiry,
                    'value' => $batchValue,
                    'action' => 'discount_or_promotion',
                    'reason' => 'Expiring soon',
                    'priority' => 'high',
                ];
            }
        }

        return [
            'warehouse_id' => $warehouseId,
            'total_recommendations' => count($recommendations),
            'total_write_off_value' => $totalWriteOffValue,
            'recommendations' => $recommendations,
        ];
    }

    /**
     * Generate shelf life analysis
     *
     * @param  int  $warehouseId  Warehouse ID
     * @return array Shelf life data
     */
    public function generateShelfLifeAnalysis(int $warehouseId): array
    {
        $batches = $this->db->table('inventory_batches as ib')
            ->join('products as p', 'ib.product_id', '=', 'p.id')
            ->where('ib.warehouse_id', $warehouseId)
            ->where('ib.status', '!=', 'recalled')
            ->select('ib.*', 'p.category', 'p.unit_cost')
            ->get();

        $shelfLifeData = [];

        foreach ($batches as $batch) {
            $manufactureDate = $batch->manufacture_date ?? now()->subDays(365);
            $expiryDate = \Carbon\Carbon::parse($batch->expiry_date);
            $totalShelfLife = \Carbon\Carbon::parse($manufactureDate)->diffInDays($expiryDate);
            $remainingShelfLife = now()->diffInDays($expiryDate, false);
            $shelfLifeUtilized = $totalShelfLife > 0 ? (($totalShelfLife - $remainingShelfLife) / $totalShelfLife) * 100 : 0;

            $shelfLifeData[] = [
                'batch_id' => $batch->id,
                'batch_number' => $batch->batch_number,
                'product_id' => $batch->product_id,
                'category' => $batch->category,
                'total_shelf_life_days' => $totalShelfLife,
                'remaining_shelf_life_days' => $remainingShelfLife,
                'shelf_life_utilized_percent' => $shelfLifeUtilized,
                'quantity' => $batch->current_quantity,
            ];
        }

        $averageShelfLifeUtilized = count($shelfLifeData) > 0
            ? array_sum(array_column($shelfLifeData, 'shelf_life_utilized_percent')) / count($shelfLifeData)
            : 0;

        return [
            'warehouse_id' => $warehouseId,
            'average_shelf_life_utilized' => $averageShelfLifeUtilized,
            'batches' => $shelfLifeData,
        ];
    }

    /**
     * Calculate total value of batches
     *
     * @param  array  $batches  Batches
     * @return float Total value
     */
    private function calculateBatchValue(array $batches): float
    {
        $totalValue = 0;

        foreach ($batches as $batch) {
            $product = $this->db->table('products')
                ->where('id', $batch['product_id'])
                ->first();
            $unitCost = $product->unit_cost ?? 0;
            $totalValue += $batch['current_quantity'] * $unitCost;
        }

        return $totalValue;
    }

    /**
     * Export expiration report to CSV
     *
     * @param  int  $warehouseId  Warehouse ID
     * @return string CSV content
     */
    public function exportToCsv(int $warehouseId): string
    {
        $report = $this->generateExpirationReport($warehouseId);

        $lines = [];
        $lines[] = 'Batch ID,Batch Number,Product ID,Quantity,Expiry Date,Days Until Expiry,Status';

        $allBatches = array_merge(
            $report['expired'],
            $report['expiring_7_days'],
            $report['expiring_30_days'],
            $report['expiring_90_days']
        );

        foreach ($allBatches as $batch) {
            $lines[] = sprintf(
                '%s,%s,%s,%s,%s,%s,%s',
                $batch['batch_id'],
                $batch['batch_number'],
                $batch['product_id'],
                $batch['current_quantity'],
                $batch['expiry_date'],
                $batch['days_until_expiry'],
                $batch['status']
            );
        }

        return implode("\n", $lines);
    }

    /**
     * Get expiration alerts
     *
     * @param  int  $warehouseId  Warehouse ID
     * @return array Alerts
     */
    public function getExpirationAlerts(int $warehouseId): array
    {
        $report = $this->generateExpirationReport($warehouseId);
        $alerts = [];

        if ($report['summary']['expired_count'] > 0) {
            $alerts[] = [
                'type' => 'critical',
                'message' => sprintf(
                    '%d expired batches found with total value of %s',
                    $report['summary']['expired_count'],
                    number_format($report['summary']['expired_value'], 2)
                ),
                'count' => $report['summary']['expired_count'],
            ];
        }

        if ($report['summary']['expiring_7_days_count'] > 0) {
            $alerts[] = [
                'type' => 'warning',
                'message' => sprintf(
                    '%d batches expiring within 7 days with total value of %s',
                    $report['summary']['expiring_7_days_count'],
                    number_format($report['summary']['expiring_7_days_value'], 2)
                ),
                'count' => $report['summary']['expiring_7_days_count'],
            ];
        }

        if ($report['summary']['expiring_30_days_count'] > 0) {
            $alerts[] = [
                'type' => 'info',
                'message' => sprintf(
                    '%d batches expiring within 30 days with total value of %s',
                    $report['summary']['expiring_30_days_count'],
                    number_format($report['summary']['expiring_30_days_value'], 2)
                ),
                'count' => $report['summary']['expiring_30_days_count'],
            ];
        }

        return [
            'warehouse_id' => $warehouseId,
            'alerts' => $alerts,
        ];
    }
}
