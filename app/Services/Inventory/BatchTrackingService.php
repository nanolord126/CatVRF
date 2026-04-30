<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;
use Illuminate\Database\DatabaseManager;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Batch Tracking Service
 *
 * Implements advanced batch management features:
 * - FEFO (First Expired First Out) for medical products
 * - Serial number validation and tracking
 * - Quarantine workflow for quality control
 * - Recall management for product recalls
 * - FIFO (First In First Out) for non-medical products
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class BatchTrackingService
{
    use WithAuditLogging;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService,
        private readonly CacheRepository $cache
    ) {}

    /**
     * Create inventory batch
     *
     * @param  int  $productId  Product ID
     * @param  int  $warehouseId  Warehouse ID
     * @param  int  $tenantId  Tenant ID
     * @param  string  $batchNumber  Batch number
     * @param  string  $expiryDate  Expiry date
     * @param  int  $quantity  Initial quantity
     * @param  string  $serialNumber  Serial number (optional)
     * @param  int  $userId  User creating
     * @return string Batch ID
     */
    public function createBatch(
        int $productId,
        int $warehouseId,
        int $tenantId,
        string $batchNumber,
        string $expiryDate,
        int $quantity,
        ?string $serialNumber,
        int $userId
    ): string {
        $batchId = Str::uuid()->toString();

        return $this->db->transaction(function () use (
            $productId,
            $warehouseId,
            $tenantId,
            $batchNumber,
            $expiryDate,
            $quantity,
            $serialNumber,
            $userId,
            $batchId
        ) {
            $this->db->table('inventory_batches')->insert([
                'id' => $batchId,
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'tenant_id' => $tenantId,
                'batch_number' => $batchNumber,
                'expiry_date' => $expiryDate,
                'quantity' => $quantity,
                'current_quantity' => $quantity,
                'serial_number' => $serialNumber,
                'status' => 'quarantine',
                'created_by' => $userId,
                'created_at' => now(),
            ]);

            $this->logAction(
                action: 'batch_created',
                entityType: 'InventoryBatch',
                entityId: $batchId,
                context: [
                    'batch_number' => $batchNumber,
                    'expiry_date' => $expiryDate,
                    'quantity' => $quantity,
                    'serial_number' => $serialNumber,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return $batchId;
        });
    }

    /**
     * Release batch from quarantine
     *
     * @param  string  $batchId  Batch ID
     * @param  int  $userId  User releasing
     * @return bool
     */
    public function releaseFromQuarantine(string $batchId, int $userId): bool
    {
        return $this->db->transaction(function () use ($batchId, $userId) {
            $batch = $this->db->table('inventory_batches')
                ->where('id', $batchId)
                ->lockForUpdate()
                ->first();

            if (! $batch) {
                throw new \RuntimeException("Batch not found: {$batchId}");
            }

            if ($batch->status !== 'quarantine') {
                throw new \RuntimeException("Batch is not in quarantine: {$batchId}");
            }

            $this->db->table('inventory_batches')
                ->where('id', $batchId)
                ->update([
                    'status' => 'available',
                    'released_at' => now(),
                    'released_by' => $userId,
                ]);

            $this->logAction(
                action: 'batch_released_from_quarantine',
                entityType: 'InventoryBatch',
                entityId: $batchId,
                context: [
                    'batch_number' => $batch->batch_number,
                ],
                userId: $userId,
                tenantId: $batch->tenant_id
            );

            return true;
        });
    }

    /**
     * Place batch on hold (recall or quality issue)
     *
     * @param  string  $batchId  Batch ID
     * @param  string  $reason  Hold reason
     * @param  int  $userId  User placing hold
     * @return bool
     */
    public function placeOnHold(string $batchId, string $reason, int $userId): bool
    {
        return $this->db->transaction(function () use ($batchId, $reason, $userId) {
            $batch = $this->db->table('inventory_batches')
                ->where('id', $batchId)
                ->lockForUpdate()
                ->first();

            if (! $batch) {
                throw new \RuntimeException("Batch not found: {$batchId}");
            }

            $this->db->table('inventory_batches')
                ->where('id', $batchId)
                ->update([
                    'status' => 'on_hold',
                    'hold_reason' => $reason,
                    'held_at' => now(),
                    'held_by' => $userId,
                ]);

            $this->logAction(
                action: 'batch_placed_on_hold',
                entityType: 'InventoryBatch',
                entityId: $batchId,
                context: [
                    'batch_number' => $batch->batch_number,
                    'reason' => $reason,
                ],
                userId: $userId,
                tenantId: $batch->tenant_id
            );

            return true;
        });
    }

    /**
     * Create recall for batch
     *
     * @param  string  $batchId  Batch ID
     * @param  string  $recallType  Recall type (voluntary, mandatory)
     * @param  string  $reason  Recall reason
     * @param  int  $tenantId  Tenant ID
     * @param  int  $userId  User creating recall
     * @return string Recall ID
     */
    public function createRecall(
        string $batchId,
        string $recallType,
        string $reason,
        int $tenantId,
        int $userId
    ): string {
        $recallId = Str::uuid()->toString();

        return $this->db->transaction(function () use (
            $batchId,
            $recallType,
            $reason,
            $tenantId,
            $userId,
            $recallId
        ) {
            $batch = $this->db->table('inventory_batches')
                ->where('id', $batchId)
                ->first();

            if (! $batch) {
                throw new \RuntimeException("Batch not found: {$batchId}");
            }

            $this->db->table('batch_recalls')->insert([
                'id' => $recallId,
                'batch_id' => $batchId,
                'recall_type' => $recallType,
                'reason' => $reason,
                'status' => 'active',
                'tenant_id' => $tenantId,
                'created_by' => $userId,
                'created_at' => now(),
            ]);

            $this->db->table('inventory_batches')
                ->where('id', $batchId)
                ->update([
                    'status' => 'recalled',
                    'recalled_at' => now(),
                ]);

            $this->logAction(
                action: 'batch_recall_created',
                entityType: 'BatchRecall',
                entityId: $recallId,
                context: [
                    'batch_id' => $batchId,
                    'batch_number' => $batch->batch_number,
                    'recall_type' => $recallType,
                    'reason' => $reason,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return $recallId;
        });
    }

    /**
     * Track serial number movement
     *
     * @param  string  $serialNumber  Serial number
     * @param  string  $movementType  Movement type (in, out, transfer)
     * @param  int  $fromLocation  From location ID
     * @param  int  $toLocation  To location ID
     * @param  string  $referenceType  Reference type (order, transfer, etc.)
     * @param  int  $referenceId  Reference ID
     * @param  int  $tenantId  Tenant ID
     * @param  int  $userId  User tracking
     * @return string Tracking ID
     */
    public function trackSerialMovement(
        string $serialNumber,
        string $movementType,
        ?int $fromLocation,
        ?int $toLocation,
        string $referenceType,
        int $referenceId,
        int $tenantId,
        int $userId
    ): string {
        $trackingId = Str::uuid()->toString();

        $this->db->table('serial_number_tracking')->insert([
            'id' => $trackingId,
            'serial_number' => $serialNumber,
            'movement_type' => $movementType,
            'from_location_id' => $fromLocation,
            'to_location_id' => $toLocation,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'tenant_id' => $tenantId,
            'tracked_by' => $userId,
            'tracked_at' => now(),
        ]);

        return $trackingId;
    }

    /**
     * Get batch traceability report
     *
     * @param  string  $batchId  Batch ID
     * @return array Traceability data
     */
    public function getBatchTraceability(string $batchId): array
    {
        $batch = $this->db->table('inventory_batches')
            ->where('id', $batchId)
            ->first();

        if (! $batch) {
            throw new \RuntimeException("Batch not found: {$batchId}");
        }

        $movements = $this->db->table('stock_movements')
            ->where('batch_id', $batchId)
            ->orderBy('created_at', 'desc')
            ->get();

        $serialTrackings = $this->db->table('serial_number_tracking')
            ->where('batch_id', $batchId)
            ->orderBy('tracked_at', 'desc')
            ->get();

        return [
            'batch' => [
                'id' => $batch->id,
                'batch_number' => $batch->batch_number,
                'expiry_date' => $batch->expiry_date,
                'quantity' => $batch->quantity,
                'status' => $batch->status,
                'serial_number' => $batch->serial_number,
            ],
            'movements' => $movements->toArray(),
            'serial_trackings' => $serialTrackings->toArray(),
        ];
    }

    /**
     * Get expired batches
     *
     * @param  int  $warehouseId  Warehouse ID
     * @return array Expired batches
     */
    public function getExpiredBatches(int $warehouseId): array
    {
        return $this->db->table('inventory_batches')
            ->where('warehouse_id', $warehouseId)
            ->where('expiry_date', '<', now())
            ->where('status', '!=', 'recalled')
            ->toArray();
    }

    /**
     * Get batches expiring soon
     *
     * @param  int  $warehouseId  Warehouse ID
     * @param  int  $days  Days threshold
     * @return array Expiring batches
     */
    public function getExpiringBatches(int $warehouseId, int $days = 30): array
    {
        return $this->db->table('inventory_batches')
            ->where('warehouse_id', $warehouseId)
            ->where('expiry_date', '>', now())
            ->where('expiry_date', '<=', now()->addDays($days))
            ->where('status', 'available')
            ->orderBy('expiry_date')
            ->get()
            ->toArray();
    }

    /**
     * Select batch using FEFO (First Expired First Out) logic
     * Critical for medical products compliance (ФЗ-61)
     *
     * @param  int  $productId  Product ID
     * @param  int  $warehouseId  Warehouse ID
     * @param  int  $quantity  Required quantity
     * @return array Selected batches with quantities
     */
    public function selectBatchesFEFO(int $productId, int $warehouseId, int $quantity): array
    {
        $batches = $this->db->table('inventory_batches')
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->where('status', 'available')
            ->where('current_quantity', '>', 0)
            ->where('expiry_date', '>', now())
            ->orderBy('expiry_date', 'asc')
            ->orderBy('manufacture_date', 'asc')
            ->lockForUpdate()
            ->get();

        $selectedBatches = [];
        $remainingQuantity = $quantity;

        foreach ($batches as $batch) {
            if ($remainingQuantity <= 0) {
                break;
            }

            $availableQuantity = $batch->current_quantity;
            $takeQuantity = min($availableQuantity, $remainingQuantity);

            $selectedBatches[] = [
                'batch_id' => $batch->id,
                'batch_number' => $batch->batch_number,
                'expiry_date' => $batch->expiry_date,
                'quantity' => $takeQuantity,
                'available_quantity' => $availableQuantity,
            ];

            $remainingQuantity -= $takeQuantity;
        }

        if ($remainingQuantity > 0) {
            throw new \RuntimeException(
                sprintf(
                    'Insufficient stock for product %d. Required: %d, Available: %d',
                    $productId,
                    $quantity,
                    $quantity - $remainingQuantity
                )
            );
        }

        $this->logger->info('FEFO batch selection completed', [
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'required_quantity' => $quantity,
            'batches_selected' => count($selectedBatches),
        ]);

        return $selectedBatches;
    }

    /**
     * Select batch using FIFO (First In First Out) logic
     * For non-medical products where expiry is not critical
     *
     * @param  int  $productId  Product ID
     * @param  int  $warehouseId  Warehouse ID
     * @param  int  $quantity  Required quantity
     * @return array Selected batches with quantities
     */
    public function selectBatchesFIFO(int $productId, int $warehouseId, int $quantity): array
    {
        $batches = $this->db->table('inventory_batches')
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->where('status', 'available')
            ->where('current_quantity', '>', 0)
            ->orderBy('created_at', 'asc')
            ->lockForUpdate()
            ->get();

        $selectedBatches = [];
        $remainingQuantity = $quantity;

        foreach ($batches as $batch) {
            if ($remainingQuantity <= 0) {
                break;
            }

            $availableQuantity = $batch->current_quantity;
            $takeQuantity = min($availableQuantity, $remainingQuantity);

            $selectedBatches[] = [
                'batch_id' => $batch->id,
                'batch_number' => $batch->batch_number,
                'quantity' => $takeQuantity,
                'available_quantity' => $availableQuantity,
            ];

            $remainingQuantity -= $takeQuantity;
        }

        if ($remainingQuantity > 0) {
            throw new \RuntimeException(
                sprintf(
                    'Insufficient stock for product %d. Required: %d, Available: %d',
                    $productId,
                    $quantity,
                    $quantity - $remainingQuantity
                )
            );
        }

        return $selectedBatches;
    }

    /**
     * FEFO Picking Order (First Expired First Out)
     * Для медицинских препаратов с коротким сроком годности
     *
     * @param  int  $productId  Product ID
     * @param  int  $quantity  Quantity to pick
     * @param  int  $warehouseId  Warehouse ID
     * @return array Picking order with batches
     */
    public function getFEFOPickingOrder(int $productId, int $quantity, int $warehouseId): array
    {
        $batches = $this->db->table('inventory_batches')
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->where('status', 'available')
            ->where('quantity', '>', 0)
            ->orderBy('expiry_date', 'asc') // FEFO: самые ранние сроки годности первыми
            ->get();

        $pickingOrder = [];
        $remainingQty = $quantity;

        foreach ($batches as $batch) {
            if ($remainingQty <= 0) {
                break;
            }

            // Проверка срока годности
            $expiryDate = \Carbon\Carbon::parse($batch->expiry_date);
            if ($expiryDate->isPast()) {
                $this->logger->warning('Skipping expired batch in FEFO picking', [
                    'batch_id' => $batch->id,
                    'batch_number' => $batch->batch_number,
                    'expiry_date' => $batch->expiry_date,
                ]);
                continue;
            }

            $pickQty = min($batch->quantity, $remainingQty);
            $pickingOrder[] = [
                'batch_id' => $batch->id,
                'batch_number' => $batch->batch_number,
                'expiry_date' => $batch->expiry_date,
                'quantity' => $pickQty,
                'serial_number' => $batch->serial_number,
                'remaining' => $batch->quantity - $pickQty,
            ];

            $remainingQty -= $pickQty;
        }

        if ($remainingQty > 0) {
            throw new \RuntimeException("Insufficient stock for FEFO picking. Need {$quantity}, available: {$quantity - $remainingQty}");
        }

        return $pickingOrder;
    }

    /**
     * Serial Number Validation for Medical Products
     * Проверка серийных номеров медицинских препаратов
     *
     * @param  string  $serialNumber  Serial number
     * @param  int  $productId  Product ID
     * @param  int  $warehouseId  Warehouse ID
     * @return array Validation result
     */
    public function validateSerialNumber(string $serialNumber, int $productId, int $warehouseId): array
    {
        // Проверка формата серийного номера
        if (!$this->isValidSerialFormat($serialNumber)) {
            return [
                'valid' => false,
                'reason' => 'Invalid serial number format',
            ];
        }

        // Проверка на дубликат
        $exists = $this->db->table('inventory_batches')
            ->where('serial_number', $serialNumber)
            ->where('warehouse_id', $warehouseId)
            ->exists();

        if ($exists) {
            return [
                'valid' => false,
                'reason' => 'Serial number already exists in warehouse',
            ];
        }

        // Проверка в черном списке (отозванные серии)
        $isBlacklisted = $this->db->table('serial_number_blacklist')
            ->where('serial_number', $serialNumber)
            ->where('is_active', true)
            ->exists();

        if ($isBlacklisted) {
            return [
                'valid' => false,
                'reason' => 'Serial number is blacklisted (recalled)',
            ];
        }

        return [
            'valid' => true,
            'reason' => 'Serial number is valid',
        ];
    }

    /**
     * Валидация формата серийного номера
     */
    private function isValidSerialFormat(string $serialNumber): bool
    {
        // Базовая валидация: минимум 8 символов, только алфавитно-цифровые
        return strlen($serialNumber) >= 8 && preg_match('/^[A-Za-z0-9\-]+$/', $serialNumber);
    }

    /**
     * Quarantine Workflow
     * Перевод партии в карантин для проверки
     *
     * @param  string  $batchId  Batch ID
     * @param  string  $reason  Quarantine reason
     * @param  int  $userId  User placing in quarantine
     * @return bool
     */
    public function placeInQuarantine(string $batchId, string $reason, int $userId): bool
    {
        return $this->db->transaction(function () use ($batchId, $reason, $userId) {
            $batch = $this->db->table('inventory_batches')
                ->where('id', $batchId)
                ->lockForUpdate()
                ->first();

            if (!$batch) {
                throw new \RuntimeException("Batch not found: {$batchId}");
            }

            $originalStatus = $batch->status;

            $this->db->table('inventory_batches')
                ->where('id', $batchId)
                ->update([
                    'status' => 'quarantine',
                    'quarantine_reason' => $reason,
                    'quarantine_started_at' => now(),
                    'quarantine_started_by' => $userId,
                ]);

            // Создание записи в журнале карантина
            $this->db->table('quarantine_log')->insert([
                'id' => (string) Str::uuid(),
                'batch_id' => $batchId,
                'action' => 'placed_in_quarantine',
                'reason' => $reason,
                'original_status' => $originalStatus,
                'performed_by' => $userId,
                'performed_at' => now(),
            ]);

            $this->logAction(
                action: 'batch_quarantined',
                entityType: 'InventoryBatch',
                entityId: $batchId,
                context: [
                    'batch_number' => $batch->batch_number,
                    'reason' => $reason,
                    'original_status' => $originalStatus,
                ],
                userId: $userId,
                tenantId: $batch->tenant_id
            );

            return true;
        });
    }

    /**
     * Release from Quarantine
     * Выпуск партии из карантина
     *
     * @param  string  $batchId  Batch ID
     * @param  string  $result  Inspection result (approved, rejected)
     * @param  string  $notes  Inspection notes
     * @param  int  $userId  User releasing
     * @return bool
     */
    public function releaseFromQuarantine(string $batchId, string $result, string $notes, int $userId): bool
    {
        return $this->db->transaction(function () use ($batchId, $result, $notes, $userId) {
            $batch = $this->db->table('inventory_batches')
                ->where('id', $batchId)
                ->lockForUpdate()
                ->first();

            if (!$batch) {
                throw new \RuntimeException("Batch not found: {$batchId}");
            }

            if ($batch->status !== 'quarantine') {
                throw new \RuntimeException("Batch is not in quarantine: {$batchId}");
            }

            $newStatus = match ($result) {
                'approved' => 'available',
                'rejected' => 'blocked',
                default => throw new \InvalidArgumentException("Invalid result: {$result}"),
            };

            $this->db->table('inventory_batches')
                ->where('id', $batchId)
                ->update([
                    'status' => $newStatus,
                    'quarantine_reason' => null,
                    'quarantine_ended_at' => now(),
                    'quarantine_ended_by' => $userId,
                    'block_reason' => $result === 'rejected' ? $notes : null,
                    'blocked_at' => $result === 'rejected' ? now() : null,
                    'blocked_by' => $result === 'rejected' ? $userId : null,
                ]);

            // Запись в журнал карантина
            $this->db->table('quarantine_log')->insert([
                'id' => (string) Str::uuid(),
                'batch_id' => $batchId,
                'action' => 'released_from_quarantine',
                'result' => $result,
                'notes' => $notes,
                'performed_by' => $userId,
                'performed_at' => now(),
            ]);

            $this->logAction(
                action: 'batch_released_from_quarantine',
                entityType: 'InventoryBatch',
                entityId: $batchId,
                context: [
                    'batch_number' => $batch->batch_number,
                    'result' => $result,
                    'new_status' => $newStatus,
                ],
                userId: $userId,
                tenantId: $batch->tenant_id
            );

            return true;
        });
    }

    /**
     * Get Quarantine Log
     * Получение журнала карантина для партии
     */
    public function getQuarantineLog(string $batchId): array
    {
        return $this->db->table('quarantine_log')
            ->where('batch_id', $batchId)
            ->orderBy('performed_at', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Check if product requires FEFO (medical/pharmaceutical)
     *
     * @param  int  $productId  Product ID
     * @return bool Requires FEFO
     */
    public function requiresFEFO(int $productId): bool
    {
        $product = $this->db->table('products')
            ->where('id', $productId)
            ->first();

        if (! $product) {
            return false;
        }

        $category = strtolower($product->category ?? '');

        $pharmaCategories = [
            'pharmaceutical',
            'medication',
            'medicine',
            'drug',
            'vaccine',
            'antibiotic',
            'insulin',
        ];

        foreach ($pharmaCategories as $pharmaCategory) {
            if (str_contains($category, $pharmaCategory)) {
                return true;
            }
        }

        // Check by SKU prefix
        return str_starts_with($product->sku ?? '', 'MED-');
    }

    /**
     * Close recall
     *
     * @param  string  $recallId  Recall ID
     * @param  string  $resolution  Resolution notes
     * @param  int  $userId  User closing
     * @return bool
     */
    public function closeRecall(string $recallId, string $resolution, int $userId): bool
    {
        return $this->db->transaction(function () use ($recallId, $resolution, $userId) {
            $recall = $this->db->table('batch_recalls')
                ->where('id', $recallId)
                ->lockForUpdate()
                ->first();

            if (! $recall) {
                throw new \RuntimeException("Recall not found: {$recallId}");
            }

            $this->db->table('batch_recalls')
                ->where('id', $recallId)
                ->update([
                    'status' => 'closed',
                    'resolution' => $resolution,
                    'closed_at' => now(),
                    'closed_by' => $userId,
                ]);

            $this->db->table('inventory_batches')
                ->where('id', $recall->batch_id)
                ->update([
                    'status' => $recall->original_status ?? 'available',
                ]);

            $this->logAction(
                action: 'batch_recall_closed',
                entityType: 'BatchRecall',
                entityId: $recallId,
                context: [
                    'resolution' => $resolution,
                ],
                userId: $userId,
                tenantId: $recall->tenant_id
            );

            return true;
        });
    }

}
