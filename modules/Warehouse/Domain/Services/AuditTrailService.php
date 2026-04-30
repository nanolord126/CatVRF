<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Services;

use Illuminate\Database\DatabaseManager;
use Psr\Log\LoggerInterface;
use App\Traits\WithAuditLogging;

/**
 * Audit Trail Service for comprehensive operation tracking
 * 
 * Сервис обеспечивает детальный аудит всех операций в WMS:
 * - Кто изменил
 * - Что изменил
 * - Когда изменил
 * - С какого IP
 * - С какой причиной
 */
final readonly class AuditTrailService
{
    use WithAuditLogging;

    public function __construct(
        private readonly PIIProtectionService $piiService,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Логирование создания склада
     */
    public function logWarehouseCreated(
        int $userId,
        string $warehouseId,
        array $data
    ): void {
        $anonymizedData = $this->piiService->anonymizeArrayForLogs($data);

        $this->logCreated('warehouse', $warehouseId, $anonymizedData, $userId);
    }

    /**
     * Логирование обновления склада
     */
    public function logWarehouseUpdated(
        int $userId,
        string $warehouseId,
        array $oldData,
        array $newData
    ): void {
        $changes = $this->calculateChanges($oldData, $newData);
        $anonymizedChanges = $this->piiService->anonymizeArrayForLogs($changes);

        $this->logUpdated('warehouse', $warehouseId, $anonymizedChanges, $userId);
    }

    /**
     * Логирование движения товара
     */
    public function logStockMovement(
        int $userId,
        string $movementId,
        string $movementType,
        string $productSku,
        int $quantity,
        ?string $reason
    ): void {
        $this->logAction('stock_movement', $movementId, [
            'movement_type' => $movementType,
            'product_sku' => $productSku,
            'quantity' => $quantity,
            'reason' => $reason,
        ], $userId);
    }

    /**
     * Логирование начала инвентаризации
     */
    public function logInventoryCountStarted(
        int $userId,
        string $inventoryCountId,
        string $countType,
        string $warehouseId
    ): void {
        $this->logAction('inventory_count_started', $inventoryCountId, [
            'count_type' => $countType,
            'warehouse_id' => $warehouseId,
        ], $userId);
    }

    /**
     * Логирование завершения инвентаризации
     */
    public function logInventoryCountCompleted(
        int $userId,
        string $inventoryCountId,
        int $totalItems,
        int $discrepancies
    ): void {
        $this->logAction('inventory_count_completed', $inventoryCountId, [
            'total_items' => $totalItems,
            'discrepancies' => $discrepancies,
        ], $userId);
    }

    /**
     * Логирование утверждения инвентаризации
     */
    public function logInventoryCountApproved(
        int $userId,
        string $inventoryCountId,
        string $approverRole
    ): void {
        $this->logAction('inventory_count_approved', $inventoryCountId, [
            'approver_role' => $approverRole,
        ], $userId);
    }

    /**
     * Логирование создания партии
     */
    public function logBatchCreated(
        int $userId,
        string $batchId,
        string $productSku,
        string $batchNumber,
        int $quantity,
        ?string $supplierName
    ): void {
        $data = [
            'product_sku' => $productSku,
            'batch_number' => $batchNumber,
            'quantity' => $quantity,
            'supplier_name' => $supplierName,
        ];

        $anonymizedData = $this->piiService->anonymizeArrayForLogs($data);

        $this->logCreated('batch', $batchId, $anonymizedData, $userId);
    }

    /**
     * Логирование списания из партии
     */
    public function logBatchDeducted(
        int $userId,
        string $batchId,
        int $quantity,
        ?string $reason
    ): void {
        $this->logAction('batch_deducted', $batchId, [
            'quantity' => $quantity,
            'reason' => $reason,
        ], $userId);
    }

    /**
     * Логирование блокировки партии (карантин)
     */
    public function logBatchQuarantined(
        int $userId,
        string $batchId,
        string $reason
    ): void {
        $this->logAction('batch_quarantined', $batchId, [
            'reason' => $reason,
        ], $userId);
    }

    /**
     * Логирование доступа к PII
     */
    public function logPIIAccess(
        int $userId,
        string $resourceType,
        string $resourceId,
        string $field
    ): void {
        $this->logger->warning('PII access detected', [
            'user_id' => $userId,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'field' => $field,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Логирование попытки несанкционированного доступа
     */
    public function logUnauthorizedAttempt(
        int $userId,
        string $action,
        string $resource
    ): void {
        $this->logger->warning('Unauthorized access attempt', [
            'user_id' => $userId,
            'action' => $action,
            'resource' => $resource,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Вычисление изменений между двумя состояниями
     */
    private function calculateChanges(array $old, array $new): array
    {
        $changes = [];

        foreach ($new as $key => $value) {
            if (!array_key_exists($key, $old)) {
                $changes[$key] = ['new' => $value];
            } elseif ($old[$key] != $value) {
                $changes[$key] = [
                    'old' => $old[$key],
                    'new' => $value,
                ];
            }
        }

        return $changes;
    }

    /**
     * Получение audit trail для сущности
     */
    public function getAuditTrail(string $entityType, string $entityId): array
    {
        // TODO: Реализовать получение из audit logs
        return [];
    }
}
