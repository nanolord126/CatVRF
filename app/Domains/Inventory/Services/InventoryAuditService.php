<?php

declare(strict_types=1);

namespace App\Domains\Inventory\Services;

use App\Domains\Inventory\Enums\InventoryCheckStatus;
use App\Domains\Inventory\Events\InventoryCheckCreated;
use App\Domains\Inventory\Events\InventoryCheckUpdated;
use App\Domains\Inventory\Models\InventoryCheck;
use App\Domains\Inventory\Models\InventoryItem;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use Psr\Log\LoggerInterface;

/**
 * Сервис инвентаризации (стоктейкинга).
 *
 * Запускает проверку, фиксирует расхождения, создаёт корректировки.
 */
final readonly class InventoryAuditService
{
    public function __construct(
        private DatabaseManager     $db,
        private FraudControlService $fraud,
        private AuditService        $audit,
        private InventoryService    $inventory,
        private Dispatcher          $events,
        private LoggerInterface     $logger,
    ) {}

    /**
     * Начать инвентаризацию на складе.
     */
    public function startAudit(int $warehouseId, int $employeeId, int $tenantId, string $correlationId): InventoryCheck
    {
        $this->fraud->check(
            userId: $tenantId,
            operationType: 'inventory_audit_start',
            amount: 0,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($warehouseId, $employeeId, $tenantId, $correlationId): InventoryCheck {
            $check = InventoryCheck::create([
                'warehouse_id'   => $warehouseId,
                'tenant_id'      => $tenantId,
                'employee_id'    => $employeeId,
                'status'         => InventoryCheckStatus::IN_PROGRESS->value,
                'correlation_id' => $correlationId,
            ]);

            $this->events->dispatch(new InventoryCheckCreated(
                inventoryCheckId: $check->id,
                warehouseId: $warehouseId,
                tenantId: $tenantId,
                correlationId: $correlationId,
            ));

            $this->audit->record(
                action: 'inventory_audit_started',
                subjectType: 'inventory_check',
                subjectId: $check->id,
                newValues: ['warehouse_id' => $warehouseId, 'employee_id' => $employeeId],
                correlationId: $correlationId,
            );

            return $check;
        });
    }

    /**
     * Завершить инвентаризацию и записать расхождения.
     *
     * @param array<int, array{product_id: int, expected: int, actual: int}> $results
     */
    public function completeAudit(int $checkId, array $results, string $correlationId): InventoryCheck
    {
        return $this->db->transaction(function () use ($checkId, $results, $correlationId): InventoryCheck {
            /** @var InventoryCheck $check */
            $check = InventoryCheck::findOrFail($checkId);

            $discrepancies = [];

            foreach ($results as $row) {
                $diff = $row['actual'] - $row['expected'];

                if ($diff !== 0) {
                    $discrepancies[] = [
                        'product_id' => $row['product_id'],
                        'expected'   => $row['expected'],
                        'actual'     => $row['actual'],
                        'diff'       => $diff,
                    ];
                }
            }

            $newStatus = $discrepancies === []
                ? InventoryCheckStatus::COMPLETED->value
                : InventoryCheckStatus::DISCREPANCY->value;

            $oldStatus = $check->status;

            $check->update([
                'status'         => $newStatus,
                'discrepancies'  => $discrepancies === [] ? null : $discrepancies,
                'correlation_id' => $correlationId,
            ]);

            $this->events->dispatch(new InventoryCheckUpdated(
                inventoryCheckId: $checkId,
                oldStatus: $oldStatus,
                newStatus: $newStatus,
                tenantId: $check->tenant_id,
                correlationId: $correlationId,
            ));

            $this->audit->record(
                action: 'inventory_audit_completed',
                subjectType: 'inventory_check',
                subjectId: $checkId,
                oldValues: ['status' => $oldStatus],
                newValues: ['status' => $newStatus, 'discrepancies_count' => count($discrepancies)],
                correlationId: $correlationId,
            );

            return $check;
        });
    }
}
