<?php declare(strict_types=1);

namespace App\Services\Inventory;



use Illuminate\Http\Request;
use Illuminate\Auth\AuthManager;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;


use Illuminate\Support\Str;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;

/**
 * InventoryAuditService — плановые и внеплановые инвентаризации.
 *
 * Правила канона:
 *  - Fraud-check перед стартом инвентаризации.
 *  - Все операции логируются через AuditService + correlation_id.
 *  - Каждая инвентаризация — отдельная запись в inventory_audits.
 *  - Расхождения (surpluses/shortages) фиксируются в поле discrepancies (json).
 *  - После завершения — обновление реальных остатков в inventories.
 *  - Tenant-scoping обязателен.
 */
final readonly class InventoryAuditService
{
    public function __construct(
        private readonly Request $request,
        private readonly AuthManager $authManager,
        private FraudControlService $fraud,
        private AuditService $audit,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * Начать инвентаризацию всего склада.
     * Создаёт запись со списком всех позиций на момент старта.
     */
    public function startAudit(
        int $tenantId,
        int $warehouseId,
        ?int $employeeId,
        string $correlationId
    ): array {
        $this->fraud->check(
            (int) ($this->authManager->id() ?? 0),
            'inventory_audit_start',
            0,
            $this->request->ip(),
            null,
            $correlationId,
        );

        return $this->db->transaction(function () use ($tenantId, $warehouseId, $employeeId, $correlationId): array {
            // Снимаем снимок текущих ожидаемых остатков
            $positions = $this->db->table('inventories')
                ->where('warehouse_id', $warehouseId)
                ->select('product_id', 'quantity', 'reserved')
                ->get();

            if ($positions->isEmpty()) {
                throw new \DomainException("Склад {$warehouseId} не содержит товарных позиций.");
            }

            $uuid = Str::uuid()->toString();

            $auditId = $this->db->table('inventory_audits')->insertGetId([
                'uuid'             => $uuid,
                'tenant_id'        => $tenantId,
                'warehouse_id'     => $warehouseId,
                'employee_id'      => $employeeId,
                'status'           => 'in_progress',
                'total_positions'  => $positions->count(),
                'checked_positions'=> 0,
                'discrepancy_count'=> 0,
                'discrepancies'    => json_encode([]),
                'started_at'       => now()->toDateTimeString(),
                'correlation_id'   => $correlationId,
                'tags'             => json_encode([]),
                'created_at'       => now()->toDateTimeString(),
                'updated_at'       => now()->toDateTimeString(),
            ]);

            // Сохраняем ожидаемые остатки как JSON-снимок (для последующего сравнения)
            $snapshot = $positions->keyBy('product_id')->toArray();

            $this->db->table('inventory_audits')
                ->where('id', $auditId)
                ->update(['discrepancies' => json_encode(['snapshot' => $snapshot])]);

            $this->logger->channel('audit')->info('Inventory audit started', [
                'audit_id'       => $auditId,
                'warehouse_id'   => $warehouseId,
                'total_positions'=> $positions->count(),
                'correlation_id' => $correlationId,
            ]);

            $this->audit->record('inventory_audit_started', 'inventory_audits', $auditId, [], [
                'warehouse_id' => $warehouseId,
                'total'        => $positions->count(),
            ], $correlationId);

            return [
                'audit_id'       => $auditId,
                'uuid'           => $uuid,
                'total_positions'=> $positions->count(),
                'positions'      => $positions->all(),
            ];
        });
    }

    /**
     * Зафиксировать фактическое количество товара для одной позиции.
     */
    public function recordActualCount(
        int $auditId,
        int $productId,
        int $actualQuantity,
        string $correlationId
    ): void {
        $this->db->transaction(function () use ($auditId, $productId, $actualQuantity, $correlationId): void {
            $audit = $this->db->table('inventory_audits')
                ->where('id', $auditId)
                ->where('status', 'in_progress')
                ->lockForUpdate()
                ->first();

            if (!$audit) {
                throw new \DomainException("Инвентаризация #{$auditId} не найдена или уже завершена.");
            }

            $discrepancies = json_decode($audit->discrepancies, true) ?? [];
            $snapshot      = $discrepancies['snapshot'] ?? [];

            $expected = (int) ($snapshot[$productId]['quantity'] ?? 0);
            $diff     = $actualQuantity - $expected;

            $discrepancies['items'][$productId] = [
                'product_id'  => $productId,
                'expected'    => $expected,
                'actual'      => $actualQuantity,
                'diff'        => $diff,
                'type'        => match (true) {
                    $diff > 0  => 'surplus',
                    $diff < 0  => 'shortage',
                    default    => 'match',
                },
                'recorded_at' => now()->toIso8601String(),
            ];

            $checkedNow      = count($discrepancies['items'] ?? []);
            $discrepancyCount = collect($discrepancies['items'])
                ->filter(fn($i) => $i['diff'] !== 0)
                ->count();

            $this->db->table('inventory_audits')
                ->where('id', $auditId)
                ->update([
                    'discrepancies'     => json_encode($discrepancies),
                    'checked_positions' => $checkedNow,
                    'discrepancy_count' => $discrepancyCount,
                    'updated_at'        => now()->toDateTimeString(),
                ]);

            $this->logger->channel('audit')->info('Inventory actual count recorded', [
                'audit_id'       => $auditId,
                'product_id'     => $productId,
                'expected'       => $expected,
                'actual'         => $actualQuantity,
                'diff'           => $diff,
                'correlation_id' => $correlationId,
            ]);
        });
    }

    /**
     * Завершить инвентаризацию.
     * Обновляет реальные остатки и выставляет статус.
     */
    public function completeAudit(int $auditId, string $correlationId): array
    {
        $this->fraud->check(
            (int) ($this->authManager->id() ?? 0),
            'inventory_audit_complete',
            0,
            $this->request->ip(),
            null,
            $correlationId,
        );

        return $this->db->transaction(function () use ($auditId, $correlationId): array {
            $audit = $this->db->table('inventory_audits')
                ->where('id', $auditId)
                ->where('status', 'in_progress')
                ->lockForUpdate()
                ->first();

            if (!$audit) {
                throw new \DomainException("Инвентаризация #{$auditId} не в статусе in_progress.");
            }

            $discrepancies = json_decode($audit->discrepancies, true) ?? [];
            $items         = $discrepancies['items'] ?? [];

            $surpluses  = 0;
            $shortages  = 0;
            $matches    = 0;

            foreach ($items as $item) {
                match ($item['type']) {
                    'shortage' => $shortages++,
                    default    => $matches++,
                };

                // Обновляем фактические остатки
                $this->db->table('inventories')
                    ->where('warehouse_id', $audit->warehouse_id)
                    ->where('product_id', $item['product_id'])
                    ->update([
                        'quantity'   => $item['actual'],
                        'updated_at' => now()->toDateTimeString(),
                    ]);
            }

            $finalStatus = ($shortages > 0 || $surpluses > 0) ? 'discrepancy' : 'completed';

            $this->db->table('inventory_audits')
                ->where('id', $auditId)
                ->update([
                    'status'           => $finalStatus,
                    'completed_at'     => now()->toDateTimeString(),
                    'discrepancy_count'=> $shortages + $surpluses,
                    'updated_at'       => now()->toDateTimeString(),
                ]);

            $this->audit->record('inventory_audit_completed', 'inventory_audits', $auditId, [], [
                'status'     => $finalStatus,
                'surpluses'  => $surpluses,
                'shortages'  => $shortages,
                'matches'    => $matches,
            ], $correlationId);

            $this->logger->channel('audit')->info('Inventory audit completed', [
                'audit_id'       => $auditId,
                'status'         => $finalStatus,
                'surpluses'      => $surpluses,
                'shortages'      => $shortages,
                'matches'        => $matches,
                'correlation_id' => $correlationId,
            ]);

            return [
                'status'    => $finalStatus,
                'surpluses' => $surpluses,
                'shortages' => $shortages,
                'matches'   => $matches,
                'items'     => $items,
            ];
        });
    }

    /**
     * Отчёт по расхождениям за период.
     */
    public function getDiscrepancyReport(int $warehouseId, Carbon $from, Carbon $to): Collection
    {
        return $this->db->table('inventory_audits')
            ->where('warehouse_id', $warehouseId)
            ->whereIn('status', ['discrepancy', 'completed'])
            ->whereBetween('completed_at', [$from->toDateTimeString(), $to->toDateTimeString()])
            ->orderByDesc('completed_at')
            ->get()
            ->map(function (object $row): array {
                $data = json_decode($row->discrepancies, true) ?? [];
                return [
                    'audit_id'         => $row->id,
                    'uuid'             => $row->uuid,
                    'completed_at'     => $row->completed_at,
                    'status'           => $row->status,
                    'discrepancy_count'=> $row->discrepancy_count,
                    'items'            => $data['items'] ?? [],
                ];
            });
    }
}
