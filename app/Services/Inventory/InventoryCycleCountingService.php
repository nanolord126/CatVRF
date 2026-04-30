<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;
use App\Enums\Inventory\CycleCountType;
use Illuminate\Database\DatabaseManager;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Inventory Cycle Counting Service
 *
 * Implements advanced inventory counting features:
 * - Cycle counting (continuous counting instead of annual)
 * - Discrepancy analysis
 * - Approval workflow
 * - Variance thresholds
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class InventoryCycleCountingService
{
    use WithAuditLogging;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService,
        private readonly CacheRepository $cache
    ) {}

    /**
     * Create cycle count plan
     *
     * @param  int  $warehouseId  Warehouse ID
     * @param  int  $tenantId  Tenant ID
     * @param  string  $countType  Count type (from CycleCountType enum)
     * @param  int  $userId  User creating plan
     * @return string Plan ID
     */
    public function createCycleCountPlan(
        int $warehouseId,
        int $tenantId,
        string $countType,
        int $userId
    ): string {
        $planId = Str::uuid()->toString();

        return $this->db->transaction(function () use (
            $warehouseId,
            $tenantId,
            $countType,
            $userId,
            $planId
        ) {
            $this->db->table('cycle_count_plans')->insert([
                'id' => $planId,
                'warehouse_id' => $warehouseId,
                'tenant_id' => $tenantId,
                'count_type' => $countType,
                'status' => 'planned',
                'scheduled_date' => now()->addDays(7),
                'created_by' => $userId,
                'created_at' => now(),
            ]);

            $itemsToCount = $this->selectItemsForCount($warehouseId, $countType);

            foreach ($itemsToCount as $item) {
                $this->db->table('cycle_count_items')->insert([
                    'id' => Str::uuid()->toString(),
                    'plan_id' => $planId,
                    'inventory_item_id' => $item['id'],
                    'expected_quantity' => $item['quantity'],
                    'status' => 'pending',
                    'created_at' => now(),
                ]);
            }

            $this->logAction(
                action: 'cycle_count_plan_created',
                entityType: 'CycleCountPlan',
                entityId: $planId,
                context: [
                    'warehouse_id' => $warehouseId,
                    'count_type' => $countType,
                    'items_count' => count($itemsToCount),
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return $planId;
        });
    }

    /**
     * Record count result
     *
     * @param  string  $countItemId  Count item ID
     * @param  int  $actualQuantity  Actual counted quantity
     * @param  string  $notes  Count notes
     * @param  int  $userId  User counting
     * @return bool
     */
    public function recordCountResult(
        string $countItemId,
        int $actualQuantity,
        string $notes,
        int $userId
    ): bool {
        return $this->db->transaction(function () use (
            $countItemId,
            $actualQuantity,
            $notes,
            $userId
        ) {
            $countItem = $this->db->table('cycle_count_items')
                ->where('id', $countItemId)
                ->lockForUpdate()
                ->first();

            if (! $countItem) {
                throw new \RuntimeException("Count item not found: {$countItemId}");
            }

            $discrepancy = $actualQuantity - $countItem->expected_quantity;
            $variancePercentage = $countItem->expected_quantity > 0
                ? abs($discrepancy / $countItem->expected_quantity) * 100
                : 100;

            $this->db->table('cycle_count_items')
                ->where('id', $countItemId)
                ->update([
                    'actual_quantity' => $actualQuantity,
                    'discrepancy' => $discrepancy,
                    'variance_percentage' => $variancePercentage,
                    'counted_by' => $userId,
                    'counted_at' => now(),
                    'notes' => $notes,
                    'status' => 'counted',
                ]);

            $plan = $this->db->table('cycle_count_plans')
                ->where('id', $countItem->plan_id)
                ->first();

            $this->logAction(
                action: 'cycle_count_recorded',
                entityType: 'CycleCountItem',
                entityId: $countItemId,
                context: [
                    'expected' => $countItem->expected_quantity,
                    'actual' => $actualQuantity,
                    'discrepancy' => $discrepancy,
                    'variance_percentage' => $variancePercentage,
                ],
                userId: $userId,
                tenantId: $plan->tenant_id
            );

            return true;
        });
    }

    /**
     * Submit count for approval
     *
     * @param  string  $planId  Plan ID
     * @param  int  $userId  User submitting
     * @return bool
     */
    public function submitForApproval(string $planId, int $userId): bool
    {
        $this->db->table('cycle_count_plans')
            ->where('id', $planId)
            ->update([
                'status' => 'pending_approval',
                'submitted_at' => now(),
                'submitted_by' => $userId,
            ]);

        $plan = $this->db->table('cycle_count_plans')
            ->where('id', $planId)
            ->first();

        $this->logAction(
            action: 'cycle_count_submitted',
            entityType: 'CycleCountPlan',
            entityId: $planId,
            context: [],
            userId: $userId,
            tenantId: $plan->tenant_id
        );

        return true;
    }

    /**
     * Approve count and adjust inventory
     *
     * @param  string  $planId  Plan ID
     * @param  int  $userId  User approving
     * @return bool
     */
    public function approveCount(string $planId, int $userId): bool
    {
        return $this->db->transaction(function () use ($planId, $userId) {
            $plan = $this->db->table('cycle_count_plans')
                ->where('id', $planId)
                ->lockForUpdate()
                ->first();

            if (! $plan) {
                throw new \RuntimeException("Plan not found: {$planId}");
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
     * Reject count
     *
     * @param  string  $planId  Plan ID
     * @param  string  $reason  Rejection reason
     * @param  int  $userId  User rejecting
     * @return bool
     */
    public function rejectCount(string $planId, string $reason, int $userId): bool
    {
        $this->db->table('cycle_count_plans')
            ->where('id', $planId)
            ->update([
                'status' => 'rejected',
                'rejected_by' => $userId,
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ]);

        $plan = $this->db->table('cycle_count_plans')
            ->where('id', $planId)
            ->first();

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
     * Get discrepancy report
     *
     * @param  string  $planId  Plan ID
     * @return array Discrepancy analysis
     */
    public function getDiscrepancyReport(string $planId): array
    {
        $items = $this->db->table('cycle_count_items')
            ->where('plan_id', $planId)
            ->get();

        $totalDiscrepancy = $items->sum('discrepancy');
        $totalValueDiscrepancy = 0;

        foreach ($items as $item) {
            $inventoryItem = $this->db->table('inventory_items')
                ->where('id', $item->inventory_item_id)
                ->first();

            if ($inventoryItem) {
                $totalValueDiscrepancy += abs($item->discrepancy) * ($inventoryItem->unit_cost ?? 0);
            }
        }

        return [
            'total_items' => $items->count(),
            'items_with_discrepancy' => $items->where('discrepancy', '!=', 0)->count(),
            'total_quantity_discrepancy' => $totalDiscrepancy,
            'total_value_discrepancy' => $totalValueDiscrepancy,
            'average_variance' => $items->avg('variance_percentage'),
            'items' => $items->map(function ($item) {
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
     * Select items for counting based on type
     *
     * @param  int  $warehouseId  Warehouse ID
     * @param  string  $countType  Count type
     * @return array Items to count
     */
    private function selectItemsForCount(int $warehouseId, string $countType): array
    {
        $query = $this->db->table('inventory_items')
            ->where('warehouse_id', $warehouseId);

        $cycleCountType = CycleCountType::tryFrom($countType) ?? CycleCountType::RANDOM;

        return match ($cycleCountType) {
            CycleCountType::A_ITEMS => $query->where('abc_class', 'A')->limit(50)->get()->toArray(),
            CycleCountType::B_ITEMS => $query->where('abc_class', 'B')->limit(100)->get()->toArray(),
            CycleCountType::C_ITEMS => $query->where('abc_class', 'C')->limit(200)->get()->toArray(),
            CycleCountType::HIGH_VALUE => $query->orderByRaw('current_stock * unit_cost DESC')->limit(50)->get()->toArray(),
            CycleCountType::RANDOM => $query->inRandomOrder()->limit(100)->get()->toArray(),
            CycleCountType::EXPIRING_SOON => $query->whereNotNull('expiry_date')
                ->where('expiry_date', '<=', now()->addDays(30))
                ->limit(100)->get()->toArray(),
            CycleCountType::HIGH_TURNOVER => $query->orderByDesc('turnover_rate')->limit(50)->get()->toArray(),
            CycleCountType::LOW_TURNOVER => $query->orderBy('turnover_rate')->limit(50)->get()->toArray(),
            CycleCountType::PROBLEMATIC => $query->whereHasDiscrepancies(true)->limit(50)->get()->toArray(),
            CycleCountType::ZONE_BASED => $query->orderBy('location')->limit(100)->get()->toArray(),
        };
    }
}
