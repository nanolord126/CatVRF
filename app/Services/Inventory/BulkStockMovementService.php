<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Bulk Stock Movement Service
 *
 * Handles batch processing of stock movements for performance:
 * - Bulk stock adjustments
 * - Bulk transfers
 * - Bulk receipts
 * - Bulk write-offs
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class BulkStockMovementService
{
    use WithAuditLogging;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Bulk stock adjustment
     *
     * @param  array<array<string, mixed>>  $adjustments  Adjustments
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @return array Result
     */
    public function bulkAdjust(array $adjustments, int $userId, int $tenantId): array
    {
        $correlationId = Str::uuid()->toString();

        return $this->db->transaction(function () use ($adjustments, $userId, $tenantId, $correlationId) {
            $results = [];
            $successCount = 0;
            $failureCount = 0;

            foreach ($adjustments as $adjustment) {
                try {
                    $this->db->table('inventory_items')
                        ->where('id', $adjustment['inventory_item_id'])
                        ->increment('current_stock', $adjustment['quantity']);

                    $this->db->table('stock_movements')->insert([
                        'uuid' => Str::uuid()->toString(),
                        'correlation_id' => $correlationId,
                        'inventory_item_id' => $adjustment['inventory_item_id'],
                        'type' => 'adjustment',
                        'quantity' => $adjustment['quantity'],
                        'reason' => $adjustment['reason'] ?? 'Bulk adjustment',
                        'created_by' => $userId,
                        'performed_by' => $adjustment['performed_by'] ?? null,
                        'created_at' => now(),
                    ]);

                    $results[] = [
                        'inventory_item_id' => $adjustment['inventory_item_id'],
                        'status' => 'success',
                    ];
                    $successCount++;
                } catch (\Exception $e) {
                    $this->logger->error('Bulk adjustment failed for item', [
                        'inventory_item_id' => $adjustment['inventory_item_id'],
                        'error' => $e->getMessage(),
                    ]);

                    $results[] = [
                        'inventory_item_id' => $adjustment['inventory_item_id'],
                        'status' => 'failed',
                        'error' => $e->getMessage(),
                    ];
                    $failureCount++;
                }
            }

            $this->logAction(
                action: 'bulk_stock_adjustment',
                entityType: 'StockMovement',
                context: [
                    'correlation_id' => $correlationId,
                    'total_items' => count($adjustments),
                    'success_count' => $successCount,
                    'failure_count' => $failureCount,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return [
                'correlation_id' => $correlationId,
                'total_items' => count($adjustments),
                'success_count' => $successCount,
                'failure_count' => $failureCount,
                'results' => $results,
            ];
        });
    }

    /**
     * Bulk stock transfer between warehouses
     *
     * @param  array<array<string, mixed>>  $transfers  Transfers
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @return array Result
     */
    public function bulkTransfer(array $transfers, int $userId, int $tenantId): array
    {
        $correlationId = Str::uuid()->toString();

        return $this->db->transaction(function () use ($transfers, $userId, $tenantId, $correlationId) {
            $results = [];
            $successCount = 0;
            $failureCount = 0;

            foreach ($transfers as $transfer) {
                try {
                    $fromWarehouseId = $transfer['from_warehouse_id'];
                    $toWarehouseId = $transfer['to_warehouse_id'];
                    $itemId = $transfer['inventory_item_id'];
                    $quantity = $transfer['quantity'];

                    $fromItem = $this->db->table('inventory_items')
                        ->where('id', $itemId)
                        ->where('warehouse_id', $fromWarehouseId)
                        ->lockForUpdate()
                        ->first();

                    if (! $fromItem || $fromItem->current_stock < $quantity) {
                        throw new \RuntimeException('Insufficient stock for transfer');
                    }

                    $this->db->table('inventory_items')
                        ->where('id', $itemId)
                        ->where('warehouse_id', $fromWarehouseId)
                        ->decrement('current_stock', $quantity);

                    $toItem = $this->db->table('inventory_items')
                        ->where('product_id', $fromItem->product_id)
                        ->where('warehouse_id', $toWarehouseId)
                        ->first();

                    if (! $toItem) {
                        $this->db->table('inventory_items')->insert([
                            'product_id' => $fromItem->product_id,
                            'sku' => $fromItem->sku,
                            'name' => $fromItem->name,
                            'warehouse_id' => $toWarehouseId,
                            'tenant_id' => $tenantId,
                            'current_stock' => $quantity,
                            'created_at' => now(),
                        ]);
                    } else {
                        $this->db->table('inventory_items')
                            ->where('id', $toItem->id)
                            ->increment('current_stock', $quantity);
                    }

                    $this->db->table('stock_movements')->insert([
                        'uuid' => Str::uuid()->toString(),
                        'correlation_id' => $correlationId,
                        'inventory_item_id' => $itemId,
                        'type' => 'transfer',
                        'quantity' => -$quantity,
                        'reason' => 'Bulk transfer out',
                        'source_type' => 'warehouse',
                        'source_id' => $toWarehouseId,
                        'created_by' => $userId,
                        'created_at' => now(),
                    ]);

                    $this->db->table('stock_movements')->insert([
                        'uuid' => Str::uuid()->toString(),
                        'correlation_id' => $correlationId,
                        'inventory_item_id' => $toItem->id ?? $this->db->getPdo()->lastInsertId(),
                        'type' => 'transfer',
                        'quantity' => $quantity,
                        'reason' => 'Bulk transfer in',
                        'source_type' => 'warehouse',
                        'source_id' => $fromWarehouseId,
                        'created_by' => $userId,
                        'created_at' => now(),
                    ]);

                    $results[] = [
                        'inventory_item_id' => $itemId,
                        'status' => 'success',
                    ];
                    $successCount++;
                } catch (\Exception $e) {
                    $this->logger->error('Bulk transfer failed for item', [
                        'inventory_item_id' => $transfer['inventory_item_id'],
                        'error' => $e->getMessage(),
                    ]);

                    $results[] = [
                        'inventory_item_id' => $transfer['inventory_item_id'],
                        'status' => 'failed',
                        'error' => $e->getMessage(),
                    ];
                    $failureCount++;
                }
            }

            $this->logAction(
                action: 'bulk_stock_transfer',
                entityType: 'StockMovement',
                context: [
                    'correlation_id' => $correlationId,
                    'total_items' => count($transfers),
                    'success_count' => $successCount,
                    'failure_count' => $failureCount,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return [
                'correlation_id' => $correlationId,
                'total_items' => count($transfers),
                'success_count' => $successCount,
                'failure_count' => $failureCount,
                'results' => $results,
            ];
        });
    }

    /**
     * Bulk stock receipt
     *
     * @param  array<array<string, mixed>>  $receipts  Receipts
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @return array Result
     */
    public function bulkReceipt(array $receipts, int $userId, int $tenantId): array
    {
        $correlationId = Str::uuid()->toString();

        return $this->db->transaction(function () use ($receipts, $userId, $tenantId, $correlationId) {
            $results = [];
            $successCount = 0;
            $failureCount = 0;

            foreach ($receipts as $receipt) {
                try {
                    $itemId = $this->ensureInventoryItemExists(
                        $receipt['product_id'],
                        $receipt['warehouse_id'],
                        $tenantId,
                        $receipt
                    );

                    $this->db->table('inventory_items')
                        ->where('id', $itemId)
                        ->increment('current_stock', $receipt['quantity']);

                    $this->db->table('stock_movements')->insert([
                        'uuid' => Str::uuid()->toString(),
                        'correlation_id' => $correlationId,
                        'inventory_item_id' => $itemId,
                        'type' => 'in',
                        'quantity' => $receipt['quantity'],
                        'reason' => 'Bulk receipt',
                        'source_type' => $receipt['source_type'] ?? 'supplier',
                        'source_id' => $receipt['source_id'] ?? null,
                        'created_by' => $userId,
                        'performed_by' => $receipt['performed_by'] ?? null,
                        'approved_by' => $receipt['approved_by'] ?? null,
                        'created_at' => now(),
                    ]);

                    $results[] = [
                        'inventory_item_id' => $itemId,
                        'status' => 'success',
                    ];
                    $successCount++;
                } catch (\Exception $e) {
                    $this->logger->error('Bulk receipt failed', [
                        'product_id' => $receipt['product_id'],
                        'error' => $e->getMessage(),
                    ]);

                    $results[] = [
                        'product_id' => $receipt['product_id'],
                        'status' => 'failed',
                        'error' => $e->getMessage(),
                    ];
                    $failureCount++;
                }
            }

            $this->logAction(
                action: 'bulk_stock_receipt',
                entityType: 'StockMovement',
                context: [
                    'correlation_id' => $correlationId,
                    'total_items' => count($receipts),
                    'success_count' => $successCount,
                    'failure_count' => $failureCount,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return [
                'correlation_id' => $correlationId,
                'total_items' => count($receipts),
                'success_count' => $successCount,
                'failure_count' => $failureCount,
                'results' => $results,
            ];
        });
    }

    /**
     * Bulk stock write-off
     *
     * @param  array<array<string, mixed>>  $writeoffs  Write-offs
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @return array Result
     */
    public function bulkWriteOff(array $writeoffs, int $userId, int $tenantId): array
    {
        $correlationId = Str::uuid()->toString();

        return $this->db->transaction(function () use ($writeoffs, $userId, $tenantId, $correlationId) {
            $results = [];
            $successCount = 0;
            $failureCount = 0;

            foreach ($writeoffs as $writeoff) {
                try {
                    $item = $this->db->table('inventory_items')
                        ->where('id', $writeoff['inventory_item_id'])
                        ->lockForUpdate()
                        ->first();

                    if (! $item || $item->current_stock < $writeoff['quantity']) {
                        throw new \RuntimeException('Insufficient stock for write-off');
                    }

                    $this->db->table('inventory_items')
                        ->where('id', $writeoff['inventory_item_id'])
                        ->decrement('current_stock', $writeoff['quantity']);

                    $this->db->table('stock_movements')->insert([
                        'uuid' => Str::uuid()->toString(),
                        'correlation_id' => $correlationId,
                        'inventory_item_id' => $writeoff['inventory_item_id'],
                        'type' => 'damage',
                        'quantity' => -$writeoff['quantity'],
                        'reason' => $writeoff['reason'] ?? 'Bulk write-off',
                        'created_by' => $userId,
                        'performed_by' => $writeoff['performed_by'] ?? null,
                        'approved_by' => $writeoff['approved_by'] ?? null,
                        'created_at' => now(),
                    ]);

                    $results[] = [
                        'inventory_item_id' => $writeoff['inventory_item_id'],
                        'status' => 'success',
                    ];
                    $successCount++;
                } catch (\Exception $e) {
                    $this->logger->error('Bulk write-off failed', [
                        'inventory_item_id' => $writeoff['inventory_item_id'],
                        'error' => $e->getMessage(),
                    ]);

                    $results[] = [
                        'inventory_item_id' => $writeoff['inventory_item_id'],
                        'status' => 'failed',
                        'error' => $e->getMessage(),
                    ];
                    $failureCount++;
                }
            }

            $this->logAction(
                action: 'bulk_stock_writeoff',
                entityType: 'StockMovement',
                context: [
                    'correlation_id' => $correlationId,
                    'total_items' => count($writeoffs),
                    'success_count' => $successCount,
                    'failure_count' => $failureCount,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return [
                'correlation_id' => $correlationId,
                'total_items' => count($writeoffs),
                'success_count' => $successCount,
                'failure_count' => $failureCount,
                'results' => $results,
            ];
        });
    }

    /**
     * Ensure inventory item exists, create if not
     *
     * @param  int  $productId  Product ID
     * @param  int  $warehouseId  Warehouse ID
     * @param  int  $tenantId  Tenant ID
     * @param  array<string, mixed>  $data  Item data
     * @return int Item ID
     */
    private function ensureInventoryItemExists(
        int $productId,
        int $warehouseId,
        int $tenantId,
        array $data
    ): int {
        $item = $this->db->table('inventory_items')
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->first();

        if ($item) {
            return $item->id;
        }

        $itemId = $this->db->table('inventory_items')->insertGetId([
            'product_id' => $productId,
            'sku' => $data['sku'] ?? "PRD-{$productId}",
            'name' => $data['name'] ?? "Product {$productId}",
            'warehouse_id' => $warehouseId,
            'tenant_id' => $tenantId,
            'current_stock' => 0,
            'unit_cost' => $data['unit_cost'] ?? 0,
            'created_at' => now(),
        ]);

        return $itemId;
    }
}
