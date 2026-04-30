<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;
use Illuminate\Database\DatabaseManager;
use Illuminate\Cache\Repository as CacheRepository;
use Psr\Log\LoggerInterface;

/**
 * Audit Trail Report Service
 *
 * Generates convenient audit trail reports for inventory operations:
 * - User activity reports
 * - Item change history
 * - Transaction logs
 * - Timeline views
 * - Exportable formats
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class AuditTrailReportService
{
    use WithAuditLogging;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService,
        private readonly CacheRepository $cache
    ) {}

    /**
     * Generate audit trail for inventory item
     *
     * @param  int  $inventoryItemId  Inventory item ID
     * @param  int  $days  Period in days
     * @return array Audit trail
     */
    public function generateItemAuditTrail(int $inventoryItemId, int $days = 90): array
    {
        $startDate = now()->subDays($days);

        $item = $this->db->table('inventory_items')
            ->where('id', $inventoryItemId)
            ->first();

        if (! $item) {
            throw new \RuntimeException("Inventory item not found: {$inventoryItemId}");
        }

        $stockMovements = $this->db->table('stock_movements')
            ->where('inventory_item_id', $inventoryItemId)
            ->where('created_at', '>=', $startDate)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($movement) {
                return [
                    'type' => 'stock_movement',
                    'movement_type' => $movement->type,
                    'quantity' => $movement->quantity,
                    'reason' => $movement->reason,
                    'source_type' => $movement->source_type,
                    'source_id' => $movement->source_id,
                    'correlation_id' => $movement->correlation_id,
                    'created_by' => $movement->created_by,
                    'created_at' => $movement->created_at,
                ];
            })
            ->toArray();

        $auditLogs = $this->db->table('audit_logs')
            ->where('entity_type', 'InventoryItem')
            ->where('entity_id', $inventoryItemId)
            ->where('created_at', '>=', $startDate)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($log) {
                return [
                    'type' => 'audit_log',
                    'action' => $log->action,
                    'context' => json_decode($log->context ?? '{}', true),
                    'user_id' => $log->user_id,
                    'created_at' => $log->created_at,
                ];
            })
            ->toArray();

        $timeline = array_merge($stockMovements, $auditLogs);
        usort($timeline, fn ($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));

        return [
            'inventory_item_id' => $inventoryItemId,
            'item_name' => $item->name,
            'item_sku' => $item->sku,
            'period_days' => $days,
            'total_events' => count($timeline),
            'stock_movements_count' => count($stockMovements),
            'audit_logs_count' => count($auditLogs),
            'timeline' => $timeline,
        ];
    }

    /**
     * Generate user activity report
     *
     * @param  int  $userId  User ID
     * @param  int  $days  Period in days
     * @return array User activity
     */
    public function generateUserActivityReport(int $userId, int $days = 30): array
    {
        $startDate = now()->subDays($days);

        $stockMovements = $this->db->table('stock_movements')
            ->where('created_by', $userId)
            ->where('created_at', '>=', $startDate)
            ->orderBy('created_at', 'desc')
            ->get();

        $auditLogs = $this->db->table('audit_logs')
            ->where('user_id', $userId)
            ->where('created_at', '>=', $startDate)
            ->orderBy('created_at', 'desc')
            ->get();

        $movementsByType = $stockMovements->groupBy('type')->map(function ($group) {
            return [
                'count' => $group->count(),
                'total_quantity' => $group->sum('quantity'),
            ];
        })->toArray();

        $actionsByType = $auditLogs->groupBy('action')->map->count()->toArray();

        $dailyActivity = [];
        for ($i = 0; $i < $days; $i++) {
            $date = now()->subDays($i)->toDateString();
            $dayMovements = $stockMovements->filter(fn ($m) => $m->created_at->toDateString() === $date);
            $dayLogs = $auditLogs->filter(fn ($l) => $l->created_at->toDateString() === $date);

            $dailyActivity[$date] = [
                'movements_count' => $dayMovements->count(),
                'audit_logs_count' => $dayLogs->count(),
                'total_quantity' => $dayMovements->sum('quantity'),
            ];
        }

        return [
            'user_id' => $userId,
            'period_days' => $days,
            'summary' => [
                'total_movements' => $stockMovements->count(),
                'total_audit_logs' => $auditLogs->count(),
                'movements_by_type' => $movementsByType,
                'actions_by_type' => $actionsByType,
            ],
            'daily_activity' => array_reverse($dailyActivity, true),
        ];
    }

    /**
     * Generate warehouse audit trail
     *
     * @param  int  $warehouseId  Warehouse ID
     * @param  int  $days  Period in days
     * @return array Warehouse audit trail
     */
    public function generateWarehouseAuditTrail(int $warehouseId, int $days = 30): array
    {
        $startDate = now()->subDays($days);

        $itemIds = $this->db->table('inventory_items')
            ->where('warehouse_id', $warehouseId)
            ->pluck('id')
            ->toArray();

        $stockMovements = $this->db->table('stock_movements')
            ->whereIn('inventory_item_id', $itemIds)
            ->where('created_at', '>=', $startDate)
            ->orderBy('created_at', 'desc')
            ->limit(1000)
            ->get();

        $auditLogs = $this->db->table('audit_logs')
            ->whereIn('entity_id', $itemIds)
            ->where('entity_type', 'InventoryItem')
            ->where('created_at', '>=', $startDate)
            ->orderBy('created_at', 'desc')
            ->limit(1000)
            ->get();

        $movementsByType = $stockMovements->groupBy('type')->map(function ($group) {
            return [
                'count' => $group->count(),
                'total_quantity' => $group->sum('quantity'),
            ];
        })->toArray();

        return [
            'warehouse_id' => $warehouseId,
            'period_days' => $days,
            'summary' => [
                'total_items' => count($itemIds),
                'total_movements' => $stockMovements->count(),
                'total_audit_logs' => $auditLogs->count(),
                'movements_by_type' => $movementsByType,
            ],
            'recent_movements' => $stockMovements->take(100)->toArray(),
            'recent_audit_logs' => $auditLogs->take(100)->toArray(),
        ];
    }

    /**
     * Generate transaction log report
     *
     * @param  int  $tenantId  Tenant ID
     * @param  string  $startDate  Start date
     * @param  string  $endDate  End date
     * @param  string|null  $transactionType  Transaction type filter
     * @return array Transaction log
     */
    public function generateTransactionLogReport(
        int $tenantId,
        string $startDate,
        string $endDate,
        ?string $transactionType = null
    ): array {
        $query = $this->db->table('stock_movements as sm')
            ->join('inventory_items as ii', 'sm.inventory_item_id', '=', 'ii.id')
            ->where('ii.tenant_id', $tenantId)
            ->where('sm.created_at', '>=', $startDate)
            ->where('sm.created_at', '<=', $endDate);

        if ($transactionType) {
            $query->where('sm.type', $transactionType);
        }

        $movements = $query->orderBy('sm.created_at', 'desc')
            ->select('sm.*', 'ii.name as item_name', 'ii.sku as item_sku')
            ->get();

        $totalQuantity = $movements->sum('quantity');
        $totalValue = 0;

        foreach ($movements as $movement) {
            $totalValue += abs($movement->quantity) * ($movement->unit_cost ?? 0);
        }

        $movementsByType = $movements->groupBy('type')->map(function ($group) {
            return [
                'count' => $group->count(),
                'total_quantity' => $group->sum('quantity'),
            ];
        })->toArray();

        return [
            'tenant_id' => $tenantId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'transaction_type' => $transactionType,
            'summary' => [
                'total_transactions' => $movements->count(),
                'total_quantity' => $totalQuantity,
                'total_value' => $totalValue,
                'movements_by_type' => $movementsByType,
            ],
            'transactions' => $movements->toArray(),
        ];
    }

    /**
     * Generate timeline view for entity
     *
     * @param  string  $entityType  Entity type
     * @param  int  $entityId  Entity ID
     * @param  int  $days  Period in days
     * @return array Timeline
     */
    public function generateTimelineView(string $entityType, int $entityId, int $days = 90): array
    {
        $startDate = now()->subDays($days);

        $auditLogs = $this->db->table('audit_logs')
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->where('created_at', '>=', $startDate)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($log) {
                return [
                    'timestamp' => $log->created_at,
                    'event_type' => 'audit_log',
                    'action' => $log->action,
                    'description' => $this->getActionDescription($log->action),
                    'user_id' => $log->user_id,
                    'context' => json_decode($log->context ?? '{}', true),
                ];
            })
            ->toArray();

        $stockMovements = [];
        if ($entityType === 'InventoryItem') {
            $stockMovements = $this->db->table('stock_movements')
                ->where('inventory_item_id', $entityId)
                ->where('created_at', '>=', $startDate)
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(function ($movement) {
                    return [
                        'timestamp' => $movement->created_at,
                        'event_type' => 'stock_movement',
                        'action' => $movement->type,
                        'description' => "Stock {$movement->type}: {$movement->quantity}",
                        'quantity' => $movement->quantity,
                        'reason' => $movement->reason,
                        'user_id' => $movement->created_by,
                    ];
                })
                ->toArray();
        }

        $timeline = array_merge($auditLogs, $stockMovements);
        usort($timeline, fn ($a, $b) => strtotime($a['timestamp']) - strtotime($b['timestamp']));

        return [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'period_days' => $days,
            'total_events' => count($timeline),
            'timeline' => $timeline,
        ];
    }

    /**
     * Export audit trail to CSV
     *
     * @param  int  $inventoryItemId  Inventory item ID
     * @param  int  $days  Period in days
     * @return string CSV content
     */
    public function exportToCsv(int $inventoryItemId, int $days = 90): string
    {
        $trail = $this->generateItemAuditTrail($inventoryItemId, $days);

        $lines = [];
        $lines[] = 'Timestamp,Type,Action,Quantity,Reason,User ID';

        foreach ($trail['timeline'] as $event) {
            $timestamp = $event['created_at'] ?? $event['timestamp'] ?? '';
            $type = $event['type'] ?? $event['event_type'] ?? '';
            $action = $event['action'] ?? $event['movement_type'] ?? '';
            $quantity = $event['quantity'] ?? '';
            $reason = $event['reason'] ?? '';
            $userId = $event['created_by'] ?? $event['user_id'] ?? '';

            $lines[] = sprintf(
                '%s,%s,%s,%s,%s,%s',
                $timestamp,
                $type,
                $action,
                $quantity,
                $reason,
                $userId
            );
        }

        return implode("\n", $lines);
    }

    /**
     * Get action description
     *
     * @param  string  $action  Action name
     * @return string Description
     */
    private function getActionDescription(string $action): string
    {
        return match ($action) {
            'created' => 'Item created',
            'updated' => 'Item updated',
            'deleted' => 'Item deleted',
            'stock_in' => 'Stock added',
            'stock_out' => 'Stock removed',
            'adjust' => 'Stock adjusted',
            'transfer' => 'Stock transferred',
            default => ucfirst(str_replace('_', ' ', $action)),
        };
    }

    /**
     * Generate discrepancy audit trail
     *
     * @param  string  $planId  Cycle count plan ID
     * @return array Discrepancy audit trail
     */
    public function generateDiscrepancyAuditTrail(string $planId): array
    {
        $plan = $this->db->table('cycle_count_plans')
            ->where('id', $planId)
            ->first();

        if (! $plan) {
            throw new \RuntimeException("Cycle count plan not found: {$planId}");
        }

        $countItems = $this->db->table('cycle_count_items')
            ->where('plan_id', $planId)
            ->where('discrepancy', '!=', 0)
            ->get();

        $discrepancies = [];

        foreach ($countItems as $item) {
            $inventoryItem = $this->db->table('inventory_items')
                ->where('id', $item->inventory_item_id)
                ->first();

            $discrepancies[] = [
                'inventory_item_id' => $item->inventory_item_id,
                'item_name' => $inventoryItem->name ?? null,
                'item_sku' => $inventoryItem->sku ?? null,
                'expected_quantity' => $item->expected_quantity,
                'actual_quantity' => $item->actual_quantity,
                'discrepancy' => $item->discrepancy,
                'variance_percentage' => $item->variance_percentage,
                'counted_by' => $item->counted_by,
                'counted_at' => $item->counted_at,
                'notes' => $item->notes,
            ];
        }

        return [
            'plan_id' => $planId,
            'warehouse_id' => $plan->warehouse_id,
            'count_type' => $plan->count_type,
            'total_discrepancies' => count($discrepancies),
            'discrepancies' => $discrepancies,
        ];
    }
}
