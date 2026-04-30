<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Inventory Validation Service
 *
 * Validates inventory data integrity and business rules:
 * - Stock level validations
 * - Movement validations
 * - Batch validations
 * - Location validations
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class InventoryValidationService
{
    use WithAuditLogging;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Validate stock movement
     *
     * @param  int  $inventoryItemId  Inventory item ID
     * @param  string  $type  Movement type
     * @param  int  $quantity  Quantity
     * @param  int|null  $warehouseId  Warehouse ID (optional)
     * @return array Validation result
     */
    public function validateStockMovement(
        int $inventoryItemId,
        string $type,
        int $quantity,
        ?int $warehouseId = null
    ): array {
        $errors = [];
        $warnings = [];

        $item = $this->db->table('inventory_items')
            ->where('id', $inventoryItemId)
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->first();

        if (! $item) {
            return [
                'valid' => false,
                'errors' => ['Inventory item not found'],
                'warnings' => [],
            ];
        }

        // Validate movement type
        $validTypes = ['in', 'out', 'transfer', 'adjustment', 'reserve', 'release', 'damage'];
        if (! in_array($type, $validTypes)) {
            $errors[] = "Invalid movement type: {$type}";
        }

        // Validate quantity
        if ($quantity <= 0) {
            $errors[] = 'Quantity must be greater than 0';
        }

        // Validate stock availability for outbound movements
        if (in_array($type, ['out', 'transfer', 'damage'])) {
            $availableStock = $item->current_stock - $item->reserved_stock;

            if ($quantity > $availableStock) {
                $errors[] = "Insufficient stock. Available: {$availableStock}, Requested: {$quantity}";
            }
        }

        // Validate against minimum stock threshold
        if ($type === 'out') {
            $projectedStock = $item->current_stock - $quantity;

            if ($projectedStock < $item->safety_stock) {
                $warnings[] = "Movement will bring stock below safety stock level";
            }

            if ($projectedStock < $item->min_stock_threshold) {
                $warnings[] = "Movement will bring stock below reorder point";
            }
        }

        // Validate against maximum stock threshold
        if ($type === 'in') {
            $projectedStock = $item->current_stock + $quantity;

            if ($item->max_stock_threshold && $projectedStock > $item->max_stock_threshold) {
                $warnings[] = "Movement will exceed maximum stock threshold";
            }
        }

        // Validate item status
        if (! $item->is_active) {
            $errors[] = 'Inventory item is not active';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Validate batch
     *
     * @param  int  $inventoryItemId  Inventory item ID
     * @param  string  $batchNumber  Batch number
     * @param  string  $expiryDate  Expiry date
     * @param  int  $quantity  Quantity
     * @return array Validation result
     */
    public function validateBatch(
        int $inventoryItemId,
        string $batchNumber,
        string $expiryDate,
        int $quantity
    ): array {
        $errors = [];
        $warnings = [];

        $item = $this->db->table('inventory_items')
            ->where('id', $inventoryItemId)
            ->first();

        if (! $item) {
            return [
                'valid' => false,
                'errors' => ['Inventory item not found'],
                'warnings' => [],
            ];
        }

        // Validate batch number uniqueness
        $existingBatch = $this->db->table('inventory_batches')
            ->where('inventory_item_id', $inventoryItemId)
            ->where('batch_number', $batchNumber)
            ->first();

        if ($existingBatch) {
            $errors[] = "Batch number {$batchNumber} already exists for this item";
        }

        // Validate expiry date
        try {
            $expiry = \Carbon\Carbon::parse($expiryDate);

            if ($expiry->isPast()) {
                $errors[] = 'Expiry date cannot be in the past';
            }

            if ($expiry->lt(now()->addDays(30))) {
                $warnings[] = 'Batch expires within 30 days';
            }
        } catch (\Exception $e) {
            $errors[] = 'Invalid expiry date format';
        }

        // Validate quantity
        if ($quantity <= 0) {
            $errors[] = 'Quantity must be greater than 0';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Validate location capacity
     *
     * @param  int  $locationId  Location ID
     * @param  int  $quantity  Quantity to add
     * @return array Validation result
     */
    public function validateLocationCapacity(int $locationId, int $quantity): array
    {
        $errors = [];
        $warnings = [];

        $location = $this->db->table('warehouse_locations')
            ->where('id', $locationId)
            ->first();

        if (! $location) {
            return [
                'valid' => false,
                'errors' => ['Location not found'],
                'warnings' => [],
            ];
        }

        if (! $location->is_active) {
            $errors[] = 'Location is not active';
        }

        $projectedUsage = $location->current_usage + $quantity;

        if ($projectedUsage > $location->capacity) {
            $errors[] = "Location capacity exceeded. Capacity: {$location->capacity}, Projected: {$projectedUsage}";
        }

        if ($projectedUsage > ($location->capacity * 0.9)) {
            $warnings[] = 'Location will be at 90%+ capacity';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Validate transfer order
     *
     * @param  int  $fromWarehouseId  Source warehouse ID
     * @param  int  $toWarehouseId  Destination warehouse ID
     * @param  array<array<string, mixed>>  $items  Items to transfer
     * @return array Validation result
     */
    public function validateTransferOrder(
        int $fromWarehouseId,
        int $toWarehouseId,
        array $items
    ): array {
        $errors = [];
        $warnings = [];

        if ($fromWarehouseId === $toWarehouseId) {
            $errors[] = 'Source and destination warehouses cannot be the same';
        }

        $fromWarehouse = $this->db->table('warehouses')
            ->where('id', $fromWarehouseId)
            ->first();

        $toWarehouse = $this->db->table('warehouses')
            ->where('id', $toWarehouseId)
            ->first();

        if (! $fromWarehouse) {
            $errors[] = 'Source warehouse not found';
        }

        if (! $toWarehouse) {
            $errors[] = 'Destination warehouse not found';
        }

        foreach ($items as $item) {
            $inventoryItemId = $item['inventory_item_id'];
            $quantity = $item['quantity'];

            $sourceItem = $this->db->table('inventory_items')
                ->where('id', $inventoryItemId)
                ->where('warehouse_id', $fromWarehouseId)
                ->first();

            if (! $sourceItem) {
                $errors[] = "Item {$inventoryItemId} not found in source warehouse";
                continue;
            }

            $availableStock = $sourceItem->current_stock - $sourceItem->reserved_stock;

            if ($quantity > $availableStock) {
                $errors[] = "Insufficient stock for item {$inventoryItemId}. Available: {$availableStock}, Requested: {$quantity}";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Validate inventory data integrity
     *
     * @param  int  $tenantId  Tenant ID
     * @param  int|null  $warehouseId  Warehouse ID (optional)
     * @return array Validation result
     */
    public function validateDataIntegrity(int $tenantId, ?int $warehouseId = null): array
    {
        $issues = [];

        $query = $this->db->table('inventory_items')
            ->where('tenant_id', $tenantId);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        $items = $query->get();

        foreach ($items as $item) {
            // Check for negative stock
            if ($item->current_stock < 0) {
                $issues[] = [
                    'type' => 'negative_stock',
                    'severity' => 'critical',
                    'item_id' => $item->id,
                    'message' => "Item {$item->sku} has negative stock: {$item->current_stock}",
                ];
            }

            // Check for negative reserved stock
            if ($item->reserved_stock < 0) {
                $issues[] = [
                    'type' => 'negative_reserved_stock',
                    'severity' => 'critical',
                    'item_id' => $item->id,
                    'message' => "Item {$item->sku} has negative reserved stock: {$item->reserved_stock}",
                ];
            }

            // Check for reserved stock exceeding current stock
            if ($item->reserved_stock > $item->current_stock) {
                $issues[] = [
                    'type' => 'reserved_exceeds_current',
                    'severity' => 'high',
                    'item_id' => $item->id,
                    'message' => "Item {$item->sku} has reserved stock exceeding current stock",
                ];
            }

            // Check for missing SKU
            if (empty($item->sku)) {
                $issues[] = [
                    'type' => 'missing_sku',
                    'severity' => 'medium',
                    'item_id' => $item->id,
                    'message' => "Item {$item->id} is missing SKU",
                ];
            }
        }

        // Check for orphaned stock movements
        $orphanedMovements = $this->db->table('stock_movements as m')
            ->leftJoin('inventory_items as i', 'm.inventory_item_id', '=', 'i.id')
            ->whereNull('i.id')
            ->count();

        if ($orphanedMovements > 0) {
            $issues[] = [
                'type' => 'orphaned_movements',
                'severity' => 'high',
                'message' => "{$orphanedMovements} orphaned stock movements found",
            ];
        }

        // Check for orphaned batches
        $orphanedBatches = $this->db->table('inventory_batches as b')
            ->leftJoin('inventory_items as i', 'b.inventory_item_id', '=', 'i.id')
            ->whereNull('i.id')
            ->count();

        if ($orphanedBatches > 0) {
            $issues[] = [
                'type' => 'orphaned_batches',
                'severity' => 'high',
                'message' => "{$orphanedBatches} orphaned batches found",
            ];
        }

        return [
            'valid' => empty($issues),
            'total_issues' => count($issues),
            'critical_issues' => count(array_filter($issues, fn ($i) => $i['severity'] === 'critical')),
            'high_issues' => count(array_filter($issues, fn ($i) => $i['severity'] === 'high')),
            'medium_issues' => count(array_filter($issues, fn ($i) => $i['severity'] === 'medium')),
            'issues' => $issues,
        ];
    }

    /**
     * Validate cycle count discrepancy
     *
     * @param  int  $cycleCountId  Cycle count ID
     * @return array Validation result
     */
    public function validateCycleCountDiscrepancy(int $cycleCountId): array
    {
        $cycleCount = $this->db->table('inventory_cycle_counts')
            ->where('id', $cycleCountId)
            ->first();

        if (! $cycleCount) {
            return [
                'valid' => false,
                'errors' => ['Cycle count not found'],
                'warnings' => [],
            ];
        }

        $items = $this->db->table('inventory_cycle_count_items')
            ->where('cycle_count_id', $cycleCountId)
            ->get();

        $discrepancies = [];

        foreach ($items as $item) {
            $systemQty = $item->system_quantity;
            $countedQty = $item->counted_quantity;

            if ($systemQty !== $countedQty) {
                $variance = $countedQty - $systemQty;
                $variancePercent = $systemQty > 0 ? ($variance / $systemQty) * 100 : 0;

                $discrepancies[] = [
                    'inventory_item_id' => $item->inventory_item_id,
                    'system_quantity' => $systemQty,
                    'counted_quantity' => $countedQty,
                    'variance' => $variance,
                    'variance_percent' => round($variancePercent, 2),
                ];
            }
        }

        return [
            'valid' => empty($discrepancies),
            'total_items' => $items->count(),
            'discrepancy_count' => count($discrepancies),
            'discrepancies' => $discrepancies,
        ];
    }
}
