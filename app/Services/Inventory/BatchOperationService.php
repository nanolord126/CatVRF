<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;
use Illuminate\Database\DatabaseManager;
use Illuminate\Cache\Repository as CacheRepository;
use Psr\Log\LoggerInterface;

/**
 * Batch Operation Service
 *
 * Handles batch operations for mass updates:
 * - Bulk stock adjustments
 * - Bulk price updates
 * - Batch creation
 * - Efficient database operations
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class BatchOperationService
{
    use WithAuditLogging;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService,
        private readonly CacheRepository $cache
    ) {}

    /**
     * Bulk update inventory quantities
     *
     * @param  array  $updates  Array of [inventory_item_id => quantity_change]
     * @param  string  $reason  Update reason
     * @param  int  $userId  User performing update
     * @return array Update results
     */
    public function bulkUpdateQuantities(array $updates, string $reason, int $userId): array
    {
        return $this->db->transaction(function () use ($updates, $reason, $userId) {
            $results = [
                'total_updates' => count($updates),
                'successful' => 0,
                'failed' => 0,
                'errors' => [],
            ];

            $movements = [];
            $now = now();

            foreach ($updates as $inventoryItemId => $quantityChange) {
                try {
                    $this->db->table('inventory_items')
                        ->where('id', $inventoryItemId)
                        ->increment('current_stock', $quantityChange);

                    $movements[] = [
                        'id' => (string) \Illuminate\Support\Str::uuid(),
                        'inventory_item_id' => $inventoryItemId,
                        'type' => $quantityChange > 0 ? 'in' : 'out',
                        'quantity' => $quantityChange,
                        'reason' => $reason,
                        'source_type' => 'batch_update',
                        'correlation_id' => \Illuminate\Support\Str::uuid(),
                        'created_by' => $userId,
                        'created_at' => $now,
                    ];

                    $results['successful']++;
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = [
                        'inventory_item_id' => $inventoryItemId,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            if (! empty($movements)) {
                $this->db->table('stock_movements')->insert($movements);
            }

            $this->cache->tags(['inventory'])->flush();

            $this->logAction(
                action: 'bulk_quantity_update',
                entityType: 'InventoryItem',
                entityId: null,
                context: $results,
                userId: $userId,
                tenantId: 0
            );

            return $results;
        });
    }

    /**
     * Bulk create inventory batches
     *
     * @param  array  $batches  Array of batch data
     * @param  int  $userId  User creating batches
     * @return array Creation results
     */
    public function bulkCreateBatches(array $batches, int $userId): array
    {
        return $this->db->transaction(function () use ($batches, $userId) {
            $results = [
                'total_batches' => count($batches),
                'successful' => 0,
                'failed' => 0,
                'errors' => [],
            ];

            $batchRecords = [];
            $now = now();

            foreach ($batches as $batchData) {
                try {
                    $batchId = \Illuminate\Support\Str::uuid()->toString();

                    $batchRecords[] = [
                        'id' => $batchId,
                        'product_id' => $batchData['product_id'],
                        'warehouse_id' => $batchData['warehouse_id'],
                        'tenant_id' => $batchData['tenant_id'],
                        'batch_number' => $batchData['batch_number'],
                        'expiry_date' => $batchData['expiry_date'],
                        'quantity' => $batchData['quantity'],
                        'current_quantity' => $batchData['quantity'],
                        'serial_number' => $batchData['serial_number'] ?? null,
                        'status' => 'quarantine',
                        'created_by' => $userId,
                        'created_at' => $now,
                    ];

                    $results['successful']++;
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = [
                        'batch_number' => $batchData['batch_number'] ?? null,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            if (! empty($batchRecords)) {
                $this->db->table('inventory_batches')->insert($batchRecords);
            }

            $this->cache->tags(['inventory', 'batches'])->flush();

            $this->logAction(
                action: 'bulk_batch_creation',
                entityType: 'InventoryBatch',
                entityId: null,
                context: $results,
                userId: $userId,
                tenantId: 0
            );

            return $results;
        });
    }

    /**
     * Bulk update prices
     *
     * @param  array  $priceUpdates  Array of [product_id => new_price]
     * @param  string  $reason  Update reason
     * @param  int  $userId  User performing update
     * @return array Update results
     */
    public function bulkUpdatePrices(array $priceUpdates, string $reason, int $userId): array
    {
        return $this->db->transaction(function () use ($priceUpdates, $reason, $userId) {
            $results = [
                'total_updates' => count($priceUpdates),
                'successful' => 0,
                'failed' => 0,
                'errors' => [],
            ];

            foreach ($priceUpdates as $productId => $newPrice) {
                try {
                    $this->db->table('products')
                        ->where('id', $productId)
                        ->update([
                            'price' => $newPrice,
                            'updated_at' => now(),
                        ]);

                    $results['successful']++;
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = [
                        'product_id' => $productId,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            $this->cache->tags(['products'])->flush();

            $this->logAction(
                action: 'bulk_price_update',
                entityType: 'Product',
                entityId: null,
                context: $results,
                userId: $userId,
                tenantId: 0
            );

            return $results;
        });
    }

    /**
     * Bulk update locations
     *
     * @param  array  $locationUpdates  Array of [inventory_item_id => new_location]
     * @param  int  $userId  User performing update
     * @return array Update results
     */
    public function bulkUpdateLocations(array $locationUpdates, int $userId): array
    {
        return $this->db->transaction(function () use ($locationUpdates, $userId) {
            $results = [
                'total_updates' => count($locationUpdates),
                'successful' => 0,
                'failed' => 0,
                'errors' => [],
            ];

            foreach ($locationUpdates as $inventoryItemId => $newLocation) {
                try {
                    $this->db->table('inventory_items')
                        ->where('id', $inventoryItemId)
                        ->update([
                            'location' => $newLocation,
                            'updated_at' => now(),
                        ]);

                    $results['successful']++;
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = [
                        'inventory_item_id' => $inventoryItemId,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            $this->cache->tags(['inventory', 'locations'])->flush();

            $this->logAction(
                action: 'bulk_location_update',
                entityType: 'InventoryItem',
                entityId: null,
                context: $results,
                userId: $userId,
                tenantId: 0
            );

            return $results;
        });
    }

    /**
     * Bulk delete items
     *
     * @param  array  $itemIds  Array of item IDs
     * @param  string  $reason  Deletion reason
     * @param  int  $userId  User performing deletion
     * @return array Deletion results
     */
    public function bulkDeleteItems(array $itemIds, string $reason, int $userId): array
    {
        return $this->db->transaction(function () use ($itemIds, $reason, $userId) {
            $results = [
                'total_items' => count($itemIds),
                'successful' => 0,
                'failed' => 0,
                'errors' => [],
            ];

            foreach ($itemIds as $itemId) {
                try {
                    $this->db->table('inventory_items')
                        ->where('id', $itemId)
                        ->delete();

                    $results['successful']++;
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = [
                        'item_id' => $itemId,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            $this->cache->tags(['inventory'])->flush();

            $this->logAction(
                action: 'bulk_item_deletion',
                entityType: 'InventoryItem',
                entityId: null,
                context: array_merge($results, ['reason' => $reason]),
                userId: $userId,
                tenantId: 0
            );

            return $results;
        });
    }

    /**
     * Bulk update batch status
     *
     * @param  array  $batchStatuses  Array of [batch_id => new_status]
     * @param  string  $reason  Update reason
     * @param  int  $userId  User performing update
     * @return array Update results
     */
    public function bulkUpdateBatchStatus(array $batchStatuses, string $reason, int $userId): array
    {
        return $this->db->transaction(function () use ($batchStatuses, $reason, $userId) {
            $results = [
                'total_updates' => count($batchStatuses),
                'successful' => 0,
                'failed' => 0,
                'errors' => [],
            ];

            foreach ($batchStatuses as $batchId => $newStatus) {
                try {
                    $this->db->table('inventory_batches')
                        ->where('id', $batchId)
                        ->update([
                            'status' => $newStatus,
                            'updated_at' => now(),
                        ]);

                    $results['successful']++;
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = [
                        'batch_id' => $batchId,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            $this->cache->tags(['inventory', 'batches'])->flush();

            $this->logAction(
                action: 'bulk_batch_status_update',
                entityType: 'InventoryBatch',
                entityId: null,
                context: array_merge($results, ['reason' => $reason]),
                userId: $userId,
                tenantId: 0
            );

            return $results;
        });
    }

    /**
     * Get batch operation status
     *
     * @param  string  $batchOperationId  Batch operation ID
     * @return array Operation status
     */
    public function getBatchOperationStatus(string $batchOperationId): array
    {
        $operation = $this->db->table('batch_operations')
            ->where('id', $batchOperationId)
            ->first();

        if (! $operation) {
            throw new \RuntimeException("Batch operation not found: {$batchOperationId}");
        }

        return [
            'operation_id' => $batchOperationId,
            'operation_type' => $operation->operation_type,
            'status' => $operation->status,
            'total_items' => $operation->total_items,
            'processed_items' => $operation->processed_items,
            'failed_items' => $operation->failed_items,
            'started_at' => $operation->started_at,
            'completed_at' => $operation->completed_at,
        ];
    }
}
