<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Inventory Reconciliation Service
 *
 * Handles inventory reconciliation processes:
 * - Physical count reconciliation
 * - System vs physical stock matching
 * - Discrepancy resolution
 * - Adjustment approval workflow
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class InventoryReconciliationService
{
    use WithAuditLogging;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Create reconciliation job
     *
     * @param  int  $warehouseId  Warehouse ID
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @return int Reconciliation job ID
     */
    public function createReconciliationJob(
        int $warehouseId,
        int $userId,
        int $tenantId
    ): int {
        $correlationId = Str::uuid()->toString();

        return $this->db->transaction(function () use (
            $warehouseId,
            $userId,
            $tenantId,
            $correlationId
        ) {
            $jobId = $this->db->table('inventory_reconciliation_jobs')->insertGetId([
                'uuid' => Str::uuid()->toString(),
                'job_number' => $this->generateJobNumber(),
                'warehouse_id' => $warehouseId,
                'status' => 'pending',
                'tenant_id' => $tenantId,
                'created_by' => $userId,
                'created_at' => now(),
            ]);

            $this->logCreated(
                entityType: 'InventoryReconciliationJob',
                entityId: $jobId,
                context: [
                    'correlation_id' => $correlationId,
                    'job_number' => $this->generateJobNumber(),
                    'warehouse_id' => $warehouseId,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return $jobId;
        });
    }

    /**
     * Add count to reconciliation job
     *
     * @param  int  $jobId  Job ID
     * @param  int  $inventoryItemId  Inventory item ID
     * @param  int  $physicalCount  Physical count
     * @param  string  $locationCode  Location code
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @return int Count ID
     */
    public function addCountToJob(
        int $jobId,
        int $inventoryItemId,
        int $physicalCount,
        string $locationCode,
        int $userId,
        int $tenantId
    ): int {
        $correlationId = Str::uuid()->toString();

        return $this->db->transaction(function () use (
            $jobId,
            $inventoryItemId,
            $physicalCount,
            $locationCode,
            $userId,
            $tenantId,
            $correlationId
        ) {
            $item = $this->db->table('inventory_items')
                ->where('id', $inventoryItemId)
                ->first();

            if (! $item) {
                throw new \RuntimeException("Inventory item {$inventoryItemId} not found");
            }

            $countId = $this->db->table('inventory_reconciliation_counts')->insertGetId([
                'uuid' => Str::uuid()->toString(),
                'reconciliation_job_id' => $jobId,
                'inventory_item_id' => $inventoryItemId,
                'system_quantity' => $item->current_stock,
                'physical_count' => $physicalCount,
                'variance' => $physicalCount - $item->current_stock,
                'location_code' => $locationCode,
                'tenant_id' => $tenantId,
                'counted_by' => $userId,
                'counted_at' => now(),
            ]);

            $this->logAction(
                action: 'reconciliation_count_added',
                entityType: 'InventoryReconciliationCount',
                entityId: $countId,
                context: [
                    'correlation_id' => $correlationId,
                    'job_id' => $jobId,
                    'inventory_item_id' => $inventoryItemId,
                    'system_quantity' => $item->current_stock,
                    'physical_count' => $physicalCount,
                    'variance' => $physicalCount - $item->current_stock,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return $countId;
        });
    }

    /**
     * Complete reconciliation job
     *
     * @param  int  $jobId  Job ID
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @return array Completion result
     */
    public function completeReconciliationJob(int $jobId, int $userId, int $tenantId): array
    {
        $correlationId = Str::uuid()->toString();

        return $this->db->transaction(function () use (
            $jobId,
            $userId,
            $tenantId,
            $correlationId
        ) {
            $job = $this->db->table('inventory_reconciliation_jobs')
                ->where('id', $jobId)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->first();

            if (! $job) {
                throw new \RuntimeException("Pending reconciliation job {$jobId} not found");
            }

            $counts = $this->db->table('inventory_reconciliation_counts')
                ->where('reconciliation_job_id', $jobId)
                ->get();

            $discrepancies = $counts->filter(fn ($c) => $c->variance !== 0);
            $totalVariance = $discrepancies->sum('variance');
            $varianceValue = 0;

            foreach ($discrepancies as $discrepancy) {
                $item = $this->db->table('inventory_items')
                    ->where('id', $discrepancy->inventory_item_id)
                    ->first();

                if ($item) {
                    $varianceValue += abs($discrepancy->variance) * ($item->unit_cost ?? 0);
                }
            }

            $this->db->table('inventory_reconciliation_jobs')
                ->where('id', $jobId)
                ->update([
                    'status' => 'completed',
                    'total_items_counted' => $counts->count(),
                    'discrepancy_count' => $discrepancies->count(),
                    'total_variance' => $totalVariance,
                    'variance_value' => $varianceValue,
                    'completed_by' => $userId,
                    'completed_at' => now(),
                ]);

            $this->logAction(
                action: 'reconciliation_job_completed',
                entityType: 'InventoryReconciliationJob',
                entityId: $jobId,
                context: [
                    'correlation_id' => $correlationId,
                    'total_items' => $counts->count(),
                    'discrepancy_count' => $discrepancies->count(),
                    'total_variance' => $totalVariance,
                    'variance_value' => $varianceValue,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return [
                'job_id' => $jobId,
                'total_items_counted' => $counts->count(),
                'discrepancy_count' => $discrepancies->count(),
                'total_variance' => $totalVariance,
                'variance_value' => $varianceValue,
                'discrepancies' => $discrepancies->toArray(),
            ];
        });
    }

    /**
     * Approve adjustment for discrepancy
     *
     * @param  int  $countId  Count ID
     * @param  string  $reason  Approval reason
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @return bool Success
     */
    public function approveAdjustment(
        int $countId,
        string $reason,
        int $userId,
        int $tenantId
    ): bool {
        $correlationId = Str::uuid()->toString();

        return $this->db->transaction(function () use (
            $countId,
            $reason,
            $userId,
            $tenantId,
            $correlationId
        ) {
            $count = $this->db->table('inventory_reconciliation_counts')
                ->where('id', $countId)
                ->where('adjustment_approved', false)
                ->lockForUpdate()
                ->first();

            if (! $count) {
                throw new \RuntimeException("Pending adjustment for count {$countId} not found");
            }

            if ($count->variance === 0) {
                throw new \RuntimeException('No variance to adjust');
            }

            $item = $this->db->table('inventory_items')
                ->where('id', $count->inventory_item_id)
                ->lockForUpdate()
                ->first();

            if (! $item) {
                throw new \RuntimeException("Inventory item {$count->inventory_item_id} not found");
            }

            $newQuantity = $item->current_stock + $count->variance;

            $this->db->table('inventory_items')
                ->where('id', $count->inventory_item_id)
                ->update(['current_stock' => $newQuantity]);

            $this->db->table('stock_movements')->insert([
                'uuid' => Str::uuid()->toString(),
                'correlation_id' => $correlationId,
                'inventory_item_id' => $count->inventory_item_id,
                'type' => 'adjustment',
                'quantity' => $count->variance,
                'reason' => "Reconciliation adjustment: {$reason}",
                'source_type' => 'reconciliation',
                'source_id' => $count->reconciliation_job_id,
                'created_by' => $userId,
                'created_at' => now(),
            ]);

            $this->db->table('inventory_reconciliation_counts')
                ->where('id', $countId)
                ->update([
                    'adjustment_approved' => true,
                    'adjustment_approved_by' => $userId,
                    'adjustment_approved_at' => now(),
                    'adjustment_reason' => $reason,
                ]);

            $this->logAction(
                action: 'reconciliation_adjustment_approved',
                entityType: 'InventoryReconciliationCount',
                entityId: $countId,
                context: [
                    'correlation_id' => $correlationId,
                    'inventory_item_id' => $count->inventory_item_id,
                    'variance' => $count->variance,
                    'old_quantity' => $item->current_stock,
                    'new_quantity' => $newQuantity,
                    'reason' => $reason,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return true;
        });
    }

    /**
     * Reject adjustment for discrepancy
     *
     * @param  int  $countId  Count ID
     * @param  string  $reason  Rejection reason
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @return bool Success
     */
    public function rejectAdjustment(
        int $countId,
        string $reason,
        int $userId,
        int $tenantId
    ): bool {
        $correlationId = Str::uuid()->toString();

        $this->db->table('inventory_reconciliation_counts')
            ->where('id', $countId)
            ->update([
                'adjustment_approved' => false,
                'adjustment_rejected' => true,
                'adjustment_rejected_by' => $userId,
                'adjustment_rejected_at' => now(),
                'adjustment_reason' => $reason,
            ]);

        $this->logAction(
            action: 'reconciliation_adjustment_rejected',
            entityType: 'InventoryReconciliationCount',
            entityId: $countId,
            context: [
                'correlation_id' => $correlationId,
                'reason' => $reason,
            ],
            userId: $userId,
            tenantId: $tenantId
        );

        return true;
    }

    /**
     * Get reconciliation summary
     *
     * @param  int  $jobId  Job ID
     * @return array Summary
     */
    public function getReconciliationSummary(int $jobId): array
    {
        $job = $this->db->table('inventory_reconciliation_jobs')
            ->where('id', $jobId)
            ->first();

        if (! $job) {
            throw new \RuntimeException("Reconciliation job {$jobId} not found");
        }

        $counts = $this->db->table('inventory_reconciliation_counts')
            ->where('reconciliation_job_id', $jobId)
            ->get();

        $discrepancies = $counts->filter(fn ($c) => $c->variance !== 0);
        $approvedAdjustments = $counts->filter(fn ($c) => $c->adjustment_approved);
        $rejectedAdjustments = $counts->filter(fn ($c) => $c->adjustment_rejected);

        return [
            'job_id' => $jobId,
            'job_number' => $job->job_number,
            'warehouse_id' => $job->warehouse_id,
            'status' => $job->status,
            'created_at' => $job->created_at->toIso8601String(),
            'completed_at' => $job->completed_at?->toIso8601String(),
            'summary' => [
                'total_items_counted' => $counts->count(),
                'discrepancy_count' => $discrepancies->count(),
                'approved_adjustments' => $approvedAdjustments->count(),
                'rejected_adjustments' => $rejectedAdjustments->count(),
                'pending_adjustments' => $discrepancies->count() - $approvedAdjustments->count() - $rejectedAdjustments->count(),
                'total_variance' => $job->total_variance ?? 0,
                'variance_value' => $job->variance_value ?? 0,
            ],
            'discrepancies' => $discrepancies->map(fn ($c) => [
                'count_id' => $c->id,
                'inventory_item_id' => $c->inventory_item_id,
                'system_quantity' => $c->system_quantity,
                'physical_count' => $c->physical_count,
                'variance' => $c->variance,
                'location_code' => $c->location_code,
                'adjustment_approved' => $c->adjustment_approved,
                'adjustment_rejected' => $c->adjustment_rejected,
            ])->toArray(),
        ];
    }

    /**
     * Generate job number
     *
     * @return string Job number
     */
    private function generateJobNumber(): string
    {
        $prefix = 'REC';
        $date = now()->format('Ymd');
        $sequence = $this->db->table('inventory_reconciliation_jobs')
            ->whereDate('created_at', today())
            ->count() + 1;

        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }
}
