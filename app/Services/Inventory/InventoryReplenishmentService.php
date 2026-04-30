<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Inventory Replenishment Service
 *
 * Manages stock replenishment workflows:
 * - Generate replenishment orders based on reorder points
 * - Calculate optimal order quantities (EOQ)
 * - Track supplier lead times
 * - Manage purchase orders
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class InventoryReplenishmentService
{
    use WithAuditLogging;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService,
        private readonly InventoryDomainService $domainService,
    ) {}

    /**
     * Generate replenishment recommendations
     *
     * @param  int  $tenantId  Tenant ID
     * @param  int  $warehouseId  Warehouse ID
     * @return array Recommendations
     */
    public function generateReplenishmentRecommendations(int $tenantId, int $warehouseId): array
    {
        $itemsNeedingReorder = $this->db->table('inventory_items')
            ->where('tenant_id', $tenantId)
            ->where('warehouse_id', $warehouseId)
            ->whereRaw('current_stock <= min_stock_threshold')
            ->where('is_active', true)
            ->get();

        $recommendations = [];

        foreach ($itemsNeedingReorder as $item) {
            $recommendedQty = $this->calculateEOQ(
                $item->id,
                $item->unit_cost ?? 0,
                $item->annual_demand ?? 0
            );

            $priority = $this->calculateReplenishmentPriority($item);

            $recommendations[] = [
                'inventory_item_id' => $item->id,
                'product_id' => $item->product_id,
                'sku' => $item->sku,
                'name' => $item->name,
                'current_stock' => $item->current_stock,
                'min_stock_threshold' => $item->min_stock_threshold,
                'max_stock_threshold' => $item->max_stock_threshold,
                'recommended_quantity' => $recommendedQty,
                'unit_cost' => $item->unit_cost,
                'estimated_cost' => $recommendedQty * ($item->unit_cost ?? 0),
                'priority' => $priority,
                'lead_time_days' => $item->lead_time_days ?? 7,
                'supplier_id' => $item->preferred_supplier_id,
            ];
        }

        usort($recommendations, fn ($a, $b) => $b['priority'] <=> $a['priority']);

        return [
            'tenant_id' => $tenantId,
            'warehouse_id' => $warehouseId,
            'total_items' => count($recommendations),
            'high_priority_count' => count(array_filter($recommendations, fn ($r) => $r['priority'] >= 8)),
            'estimated_total_cost' => array_sum(array_column($recommendations, 'estimated_cost')),
            'recommendations' => $recommendations,
        ];
    }

    /**
     * Calculate Economic Order Quantity (EOQ)
     *
     * EOQ = sqrt(2 * D * S / H)
     * D = Annual demand
     * S = Ordering cost per order
     * H = Holding cost per unit per year
     *
     * @param  int  $inventoryItemId  Inventory item ID
     * @param  float  $unitCost  Unit cost
     * @param  int  $annualDemand  Annual demand
     * @return int EOQ
     */
    public function calculateEOQ(int $inventoryItemId, float $unitCost, int $annualDemand): int
    {
        if ($annualDemand <= 0 || $unitCost <= 0) {
            return 0;
        }

        $orderingCost = 50.0; // Default ordering cost
        $holdingCostRate = 0.25; // 25% of unit cost per year
        $holdingCost = $unitCost * $holdingCostRate;

        $eoq = sqrt((2 * $annualDemand * $orderingCost) / $holdingCost);

        return (int) round($eoq);
    }

    /**
     * Create purchase order from recommendations
     *
     * @param  array<int>  $itemIds  Item IDs to include
     * @param  int  $supplierId  Supplier ID
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @return int Purchase order ID
     */
    public function createPurchaseOrder(
        array $itemIds,
        int $supplierId,
        int $userId,
        int $tenantId
    ): int {
        $correlationId = Str::uuid()->toString();

        return $this->db->transaction(function () use (
            $itemIds,
            $supplierId,
            $userId,
            $tenantId,
            $correlationId
        ) {
            $items = $this->db->table('inventory_items')
                ->whereIn('id', $itemIds)
                ->where('tenant_id', $tenantId)
                ->get();

            if ($items->isEmpty()) {
                throw new \RuntimeException('No valid items found for purchase order');
            }

            $totalAmount = 0;
            $orderItems = [];

            foreach ($items as $item) {
                $qty = $this->calculateEOQ(
                    $item->id,
                    $item->unit_cost ?? 0,
                    $item->annual_demand ?? 0
                );

                if ($qty <= 0) {
                    $qty = $item->max_stock_threshold - $item->current_stock;
                }

                $lineTotal = $qty * ($item->unit_cost ?? 0);
                $totalAmount += $lineTotal;

                $orderItems[] = [
                    'inventory_item_id' => $item->id,
                    'quantity' => $qty,
                    'unit_cost' => $item->unit_cost ?? 0,
                    'line_total' => $lineTotal,
                ];
            }

            $purchaseOrderId = $this->db->table('purchase_orders')->insertGetId([
                'uuid' => Str::uuid()->toString(),
                'po_number' => $this->generatePONumber(),
                'supplier_id' => $supplierId,
                'tenant_id' => $tenantId,
                'status' => 'draft',
                'total_amount' => $totalAmount,
                'currency' => 'USD',
                'created_by' => $userId,
                'created_at' => now(),
            ]);

            foreach ($orderItems as $orderItem) {
                $this->db->table('purchase_order_items')->insert(array_merge($orderItem, [
                    'purchase_order_id' => $purchaseOrderId,
                    'tenant_id' => $tenantId,
                    'created_at' => now(),
                ]));
            }

            $this->logCreated(
                entityType: 'PurchaseOrder',
                entityId: $purchaseOrderId,
                context: [
                    'correlation_id' => $correlationId,
                    'po_number' => $this->generatePONumber(),
                    'supplier_id' => $supplierId,
                    'total_amount' => $totalAmount,
                    'item_count' => count($orderItems),
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return $purchaseOrderId;
        });
    }

    /**
     * Update supplier lead time
     *
     * @param  int  $supplierId  Supplier ID
     * @param  int  $inventoryItemId  Inventory item ID
     * @param  int  $leadTimeDays  Lead time in days
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @return bool Success
     */
    public function updateSupplierLeadTime(
        int $supplierId,
        int $inventoryItemId,
        int $leadTimeDays,
        int $userId,
        int $tenantId
    ): bool {
        $correlationId = Str::uuid()->toString();

        return $this->db->transaction(function () use (
            $supplierId,
            $inventoryItemId,
            $leadTimeDays,
            $userId,
            $tenantId,
            $correlationId
        ) {
            $existing = $this->db->table('supplier_lead_times')
                ->where('supplier_id', $supplierId)
                ->where('inventory_item_id', $inventoryItemId)
                ->first();

            if ($existing) {
                $this->db->table('supplier_lead_times')
                    ->where('id', $existing->id)
                    ->update([
                        'lead_time_days' => $leadTimeDays,
                        'updated_at' => now(),
                    ]);
            } else {
                $this->db->table('supplier_lead_times')->insert([
                    'supplier_id' => $supplierId,
                    'inventory_item_id' => $inventoryItemId,
                    'lead_time_days' => $leadTimeDays,
                    'tenant_id' => $tenantId,
                    'created_at' => now(),
                ]);
            }

            // Update item's lead time
            $this->db->table('inventory_items')
                ->where('id', $inventoryItemId)
                ->update([
                    'lead_time_days' => $leadTimeDays,
                    'updated_at' => now(),
                ]);

            // Recalculate reorder point
            $this->domainService->calculateReorderPoint($inventoryItemId, $leadTimeDays);

            $this->logAction(
                action: 'supplier_lead_time_updated',
                entityType: 'SupplierLeadTime',
                entityId: $existing->id ?? 0,
                context: [
                    'correlation_id' => $correlationId,
                    'supplier_id' => $supplierId,
                    'inventory_item_id' => $inventoryItemId,
                    'lead_time_days' => $leadTimeDays,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return true;
        });
    }

    /**
     * Get supplier performance metrics
     *
     * @param  int  $supplierId  Supplier ID
     * @param  int  $days  Number of days to analyze
     * @return array Performance metrics
     */
    public function getSupplierPerformance(int $supplierId, int $days = 90): array
    {
        $orders = $this->db->table('purchase_orders')
            ->where('supplier_id', $supplierId)
            ->where('created_at', '>=', now()->subDays($days))
            ->get();

        $totalOrders = $orders->count();
        $onTimeOrders = $orders->where('delivered_on_time', true)->count();
        $onTimeRate = $totalOrders > 0 ? ($onTimeOrders / $totalOrders) * 100 : 0;

        $averageLeadTime = $orders->avg('actual_lead_time_days') ?? 0;

        $totalItems = $orders->sum('total_items');
        $defectiveItems = $orders->sum('defective_items');
        $defectRate = $totalItems > 0 ? ($defectiveItems / $totalItems) * 100 : 0;

        return [
            'supplier_id' => $supplierId,
            'period_days' => $days,
            'total_orders' => $totalOrders,
            'on_time_orders' => $onTimeOrders,
            'on_time_rate' => round($onTimeRate, 2),
            'average_lead_time_days' => round($averageLeadTime, 2),
            'total_items' => $totalItems,
            'defective_items' => $defectiveItems,
            'defect_rate' => round($defectRate, 2),
            'performance_score' => $this->calculateSupplierPerformanceScore(
                $onTimeRate,
                $averageLeadTime,
                $defectRate
            ),
        ];
    }

    /**
     * Calculate replenishment priority (1-10, higher = more urgent)
     *
     * @param  mixed  $item  Inventory item
     * @return int Priority score
     */
    private function calculateReplenishmentPriority($item): int
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

        if ($item->abc_class === 'A') {
            return 4;
        }

        if ($item->abc_class === 'B') {
            return 3;
        }

        return 2;
    }

    /**
     * Calculate supplier performance score (0-100)
     *
     * @param  float  $onTimeRate  On-time delivery rate
     * @param  float  $averageLeadTime  Average lead time
     * @param  float  $defectRate  Defect rate
     * @return float Performance score
     */
    private function calculateSupplierPerformanceScore(
        float $onTimeRate,
        float $averageLeadTime,
        float $defectRate
    ): float {
        $onTimeScore = $onTimeRate * 0.4;
        $leadTimeScore = max(0, 100 - ($averageLeadTime * 2)) * 0.3;
        $defectScore = max(0, 100 - ($defectRate * 5)) * 0.3;

        return round($onTimeScore + $leadTimeScore + $defectScore, 2);
    }

    /**
     * Generate purchase order number
     *
     * @return string PO number
     */
    private function generatePONumber(): string
    {
        $prefix = 'PO';
        $date = now()->format('Ymd');
        $sequence = $this->db->table('purchase_orders')
            ->whereDate('created_at', today())
            ->count() + 1;

        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }
}
