<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;
use Illuminate\Database\DatabaseManager;
use Illuminate\Cache\Repository as CacheRepository;
use Psr\Log\LoggerInterface;

/**
 * Approval Workflow Service
 *
 * Manages approval workflows for inventory operations including:
 * - Cycle count approvals
 * - Adjustment approvals
 * - Transfer approvals
 * - Write-off approvals
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class ApprovalWorkflowService
{
    use WithAuditLogging;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService,
        private readonly CacheRepository $cache
    ) {}

    /**
     * Submit cycle count for approval
     *
     * @param  string  $planId  Cycle count plan ID
     * @param  int  $userId  User submitting
     * @return bool
     */
    public function submitCycleCountForApproval(string $planId, int $userId): bool
    {
        return $this->db->transaction(function () use ($planId, $userId) {
            $plan = $this->db->table('cycle_count_plans')
                ->where('id', $planId)
                ->lockForUpdate()
                ->first();

            if (! $plan) {
                throw new \RuntimeException("Cycle count plan not found: {$planId}");
            }

            if ($plan->status === 'pending_approval' || $plan->status === 'completed') {
                throw new \RuntimeException("Plan already submitted or completed: {$planId}");
            }

            $this->db->table('cycle_count_plans')
                ->where('id', $planId)
                ->update([
                    'status' => 'pending_approval',
                    'submitted_at' => now(),
                    'submitted_by' => $userId,
                ]);

            $this->logAction(
                action: 'cycle_count_submitted_for_approval',
                entityType: 'CycleCountPlan',
                entityId: $planId,
                context: [],
                userId: $userId,
                tenantId: $plan->tenant_id
            );

            return true;
        });
    }

    /**
     * Approve cycle count
     *
     * @param  string  $planId  Cycle count plan ID
     * @param  int  $userId  User approving
     * @return bool
     */
    public function approveCycleCount(string $planId, int $userId): bool
    {
        return $this->db->transaction(function () use ($planId, $userId) {
            $plan = $this->db->table('cycle_count_plans')
                ->where('id', $planId)
                ->lockForUpdate()
                ->first();

            if (! $plan) {
                throw new \RuntimeException("Cycle count plan not found: {$planId}");
            }

            if ($plan->status !== 'pending_approval') {
                throw new \RuntimeException("Plan is not pending approval: {$planId}");
            }

            $countItems = $this->db->table('cycle_count_items')
                ->where('plan_id', $planId)
                ->where('status', 'counted')
                ->get();

            foreach ($countItems as $item) {
                if ($item->discrepancy !== 0) {
                    $this->db->table('inventory_items')
                        ->where('id', $item->inventory_item_id)
                        ->increment('current_stock', $item->discrepancy);

                    $this->db->table('stock_movements')->insert([
                        'inventory_item_id' => $item->inventory_item_id,
                        'type' => 'adjust',
                        'quantity' => $item->discrepancy,
                        'reason' => 'Cycle count adjustment',
                        'source_type' => 'cycle_count',
                        'source_id' => $planId,
                        'correlation_id' => $planId,
                        'created_at' => now(),
                    ]);
                }

                $this->db->table('cycle_count_items')
                    ->where('id', $item->id)
                    ->update([
                        'status' => 'approved',
                        'approved_by' => $userId,
                        'approved_at' => now(),
                    ]);
            }

            $this->db->table('cycle_count_plans')
                ->where('id', $planId)
                ->update([
                    'status' => 'completed',
                    'approved_by' => $userId,
                    'approved_at' => now(),
                ]);

            $this->logAction(
                action: 'cycle_count_approved',
                entityType: 'CycleCountPlan',
                entityId: $planId,
                context: [
                    'items_adjusted' => $countItems->where('discrepancy', '!=', 0)->count(),
                ],
                userId: $userId,
                tenantId: $plan->tenant_id
            );

            $this->cache->tags(['inventory'])->flush();

            return true;
        });
    }

    /**
     * Reject cycle count
     *
     * @param  string  $planId  Cycle count plan ID
     * @param  string  $reason  Rejection reason
     * @param  int  $userId  User rejecting
     * @return bool
     */
    public function rejectCycleCount(string $planId, string $reason, int $userId): bool
    {
        $plan = $this->db->table('cycle_count_plans')
            ->where('id', $planId)
            ->first();

        if (! $plan) {
            throw new \RuntimeException("Cycle count plan not found: {$planId}");
        }

        $this->db->table('cycle_count_plans')
            ->where('id', $planId)
            ->update([
                'status' => 'rejected',
                'rejected_by' => $userId,
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ]);

        $this->logAction(
            action: 'cycle_count_rejected',
            entityType: 'CycleCountPlan',
            entityId: $planId,
            context: [
                'reason' => $reason,
            ],
            userId: $userId,
            tenantId: $plan->tenant_id
        );

        return true;
    }

    /**
     * Submit adjustment for approval
     *
     * @param  string  $adjustmentId  Adjustment ID
     * @param  int  $userId  User submitting
     * @return bool
     */
    public function submitAdjustmentForApproval(string $adjustmentId, int $userId): bool
    {
        return $this->db->transaction(function () use ($adjustmentId, $userId) {
            $adjustment = $this->db->table('inventory_adjustments')
                ->where('id', $adjustmentId)
                ->lockForUpdate()
                ->first();

            if (! $adjustment) {
                throw new \RuntimeException("Adjustment not found: {$adjustmentId}");
            }

            if ($adjustment->status === 'pending_approval' || $adjustment->status === 'approved') {
                throw new \RuntimeException("Adjustment already submitted or approved: {$adjustmentId}");
            }

            $this->db->table('inventory_adjustments')
                ->where('id', $adjustmentId)
                ->update([
                    'status' => 'pending_approval',
                    'submitted_at' => now(),
                    'submitted_by' => $userId,
                ]);

            $this->logAction(
                action: 'adjustment_submitted_for_approval',
                entityType: 'InventoryAdjustment',
                entityId: $adjustmentId,
                context: [],
                userId: $userId,
                tenantId: $adjustment->tenant_id
            );

            return true;
        });
    }

    /**
     * Approve adjustment
     *
     * @param  string  $adjustmentId  Adjustment ID
     * @param  int  $userId  User approving
     * @return bool
     */
    public function approveAdjustment(string $adjustmentId, int $userId): bool
    {
        return $this->db->transaction(function () use ($adjustmentId, $userId) {
            $adjustment = $this->db->table('inventory_adjustments')
                ->where('id', $adjustmentId)
                ->lockForUpdate()
                ->first();

            if (! $adjustment) {
                throw new \RuntimeException("Adjustment not found: {$adjustmentId}");
            }

            if ($adjustment->status !== 'pending_approval') {
                throw new \RuntimeException("Adjustment is not pending approval: {$adjustmentId}");
            }

            $this->db->table('inventory_items')
                ->where('id', $adjustment->inventory_item_id)
                ->increment('current_stock', $adjustment->quantity);

            $this->db->table('stock_movements')->insert([
                'inventory_item_id' => $adjustment->inventory_item_id,
                'type' => 'adjust',
                'quantity' => $adjustment->quantity,
                'reason' => $adjustment->reason,
                'source_type' => 'adjustment',
                'source_id' => $adjustmentId,
                'correlation_id' => $adjustmentId,
                'created_at' => now(),
            ]);

            $this->db->table('inventory_adjustments')
                ->where('id', $adjustmentId)
                ->update([
                    'status' => 'approved',
                    'approved_by' => $userId,
                    'approved_at' => now(),
                ]);

            $this->logAction(
                action: 'adjustment_approved',
                entityType: 'InventoryAdjustment',
                entityId: $adjustmentId,
                context: [
                    'quantity' => $adjustment->quantity,
                ],
                userId: $userId,
                tenantId: $adjustment->tenant_id
            );

            $this->cache->tags(['inventory'])->flush();

            return true;
        });
    }

    /**
     * Reject adjustment
     *
     * @param  string  $adjustmentId  Adjustment ID
     * @param  string  $reason  Rejection reason
     * @param  int  $userId  User rejecting
     * @return bool
     */
    public function rejectAdjustment(string $adjustmentId, string $reason, int $userId): bool
    {
        $adjustment = $this->db->table('inventory_adjustments')
            ->where('id', $adjustmentId)
            ->first();

        if (! $adjustment) {
            throw new \RuntimeException("Adjustment not found: {$adjustmentId}");
        }

        $this->db->table('inventory_adjustments')
            ->where('id', $adjustmentId)
            ->update([
                'status' => 'rejected',
                'rejected_by' => $userId,
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ]);

        $this->logAction(
            action: 'adjustment_rejected',
            entityType: 'InventoryAdjustment',
            entityId: $adjustmentId,
            context: [
                'reason' => $reason,
            ],
            userId: $userId,
            tenantId: $adjustment->tenant_id
        );

        return true;
    }

    /**
     * Get pending approvals for user
     *
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @return array Pending approvals
     */
    public function getPendingApprovals(int $userId, int $tenantId): array
    {
        $cycleCounts = $this->db->table('cycle_count_plans')
            ->where('tenant_id', $tenantId)
            ->where('status', 'pending_approval')
            ->get()
            ->map(function ($plan) {
                return [
                    'type' => 'cycle_count',
                    'id' => $plan->id,
                    'warehouse_id' => $plan->warehouse_id,
                    'count_type' => $plan->count_type,
                    'submitted_at' => $plan->submitted_at,
                    'submitted_by' => $plan->submitted_by,
                ];
            })
            ->toArray();

        $adjustments = $this->db->table('inventory_adjustments')
            ->where('tenant_id', $tenantId)
            ->where('status', 'pending_approval')
            ->get()
            ->map(function ($adjustment) {
                return [
                    'type' => 'adjustment',
                    'id' => $adjustment->id,
                    'inventory_item_id' => $adjustment->inventory_item_id,
                    'quantity' => $adjustment->quantity,
                    'reason' => $adjustment->reason,
                    'submitted_at' => $adjustment->submitted_at,
                    'submitted_by' => $adjustment->submitted_by,
                ];
            })
            ->toArray();

        return array_merge($cycleCounts, $adjustments);
    }

    /**
     * Check if approval is required based on threshold
     *
     * @param  string  $type  Approval type (cycle_count, adjustment, transfer, write_off)
     * @param  float  $value  Value to check
     * @param  int  $tenantId  Tenant ID
     * @return bool Requires approval
     */
    public function requiresApproval(string $type, float $value, int $tenantId): bool
    {
        $threshold = $this->db->table('approval_thresholds')
            ->where('tenant_id', $tenantId)
            ->where('type', $type)
            ->value('threshold');

        if ($threshold === null) {
            return $value > 1000;
        }

        return $value >= $threshold;
    }

    /**
     * Set approval threshold
     *
     * @param  string  $type  Approval type
     * @param  float  $threshold  Threshold value
     * @param  int  $tenantId  Tenant ID
     * @param  int  $userId  User setting threshold
     * @return bool
     */
    public function setApprovalThreshold(string $type, float $threshold, int $tenantId, int $userId): bool
    {
        $existing = $this->db->table('approval_thresholds')
            ->where('tenant_id', $tenantId)
            ->where('type', $type)
            ->first();

        if ($existing) {
            $this->db->table('approval_thresholds')
                ->where('id', $existing->id)
                ->update([
                    'threshold' => $threshold,
                    'updated_by' => $userId,
                    'updated_at' => now(),
                ]);
        } else {
            $this->db->table('approval_thresholds')->insert([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'tenant_id' => $tenantId,
                'type' => $type,
                'threshold' => $threshold,
                'created_by' => $userId,
                'created_at' => now(),
            ]);
        }

        $this->logAction(
            action: 'approval_threshold_updated',
            entityType: 'ApprovalThreshold',
            entityId: $existing->id ?? null,
            context: [
                'type' => $type,
                'threshold' => $threshold,
            ],
            userId: $userId,
            tenantId: $tenantId
        );

        return true;
    }
}
