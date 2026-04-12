<?php declare(strict_types=1);

namespace App\Jobs;


use App\Services\Inventory\InventoryAuditService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;


use Illuminate\Support\Str;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;

/**
 * InventoryAuditJob — плановая инвентаризация всех складов tenant'а.
 *
 * Запускается ежеквартально через Kernel::schedule().
 * Для каждого активного склада запускает полный цикл аудита:
 *   1. startAudit() — фиксирует снимок остатков.
 *   2. Заполняет actual = expected (нет физического пересчёта при авто-запуске).
 *   3. completeAudit() — закрывает запись.
 *
 * При ручной инвентаризации actual-значения вносятся через
 * InventoryAuditService::recordActualCount() (вручную или через контроллер).
 */
final class InventoryAuditJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var int Количество попыток перед провалом */
    public int $tries = 2;

    /** @var int Тайм-аут одного прогона, сек */
    public int $timeout = 300;

    public function __construct(
        private ?int $tenantId = null,
        private ?int $warehouseId = null,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
    ) {}

    public function handle(InventoryAuditService $auditor): void
    {
        $correlationId = Str::uuid()->toString();

        // Если указан конкретный склад — аудитируем только его
        $warehouses = $this->buildWarehouseQuery();

        if ($warehouses->isEmpty()) {
            $this->logger->channel('audit')->info('InventoryAuditJob: no warehouses to audit', [
                'tenant_id'    => $this->tenantId,
                'warehouse_id' => $this->warehouseId,
            ]);
            return;
        }

        foreach ($warehouses as $warehouse) {
            $cid = Str::uuid()->toString();

            try {
                $result = $auditor->startAudit(
                    (int) $warehouse->tenant_id,
                    (int) $warehouse->id,
                    null, // авто-запуск без привязки к сотруднику
                    $cid,
                );

                // При плановом авто-запуске фиксируем фактическое = ожидаемое
                // (нет физических ревизоров — только сверка книжного остатка с собой).
                foreach ($result['positions'] as $position) {
                    $auditor->recordActualCount(
                        $result['audit_id'],
                        (int) $position->product_id,
                        (int) $position->quantity,  // actual = book value
                        $cid,
                    );
                }

                $summary = $auditor->completeAudit($result['audit_id'], $cid);

                $this->logger->channel('audit')->info('InventoryAuditJob: warehouse completed', [
                    'warehouse_id'   => $warehouse->id,
                    'status'         => $summary['status'],
                    'discrepancies'  => $summary['surpluses'] + $summary['shortages'],
                    'correlation_id' => $cid,
                ]);
            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error('InventoryAuditJob: failed for warehouse', [
                    'warehouse_id'   => $warehouse->id,
                    'error'          => $e->getMessage(),
                    'correlation_id' => $cid,
                ]);
            }
        }
    }

    // ── helpers ──────────────────────────────────────────────────────────────

    private function buildWarehouseQuery(): \Illuminate\Support\Collection
    {
        $query = $this->db->table('warehouses')->where('is_active', true);

        if ($this->tenantId !== null) {
            $query->where('tenant_id', $this->tenantId);
        }

        if ($this->warehouseId !== null) {
            $query->where('id', $this->warehouseId);
        }

        return $query->select('id', 'tenant_id', 'name')->get();
    }
}
