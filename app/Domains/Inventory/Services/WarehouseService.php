<?php

declare(strict_types=1);

namespace App\Domains\Inventory\Services;

use App\Domains\Inventory\Models\Warehouse;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Database\DatabaseManager;
use Psr\Log\LoggerInterface;

/**
 * Сервис управления складами.
 *
 * CRUD для Warehouse + поиск ближайшего склада.
 */
final readonly class WarehouseService
{
    public function __construct(
        private DatabaseManager     $db,
        private FraudControlService $fraud,
        private AuditService        $audit,
        private LoggerInterface     $logger,
    ) {}

    /**
     * Создать новый склад.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data, string $correlationId): Warehouse
    {
        $this->fraud->check(
            userId: (int) ($data['tenant_id'] ?? 0),
            operationType: 'warehouse_create',
            amount: 0,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($data, $correlationId): Warehouse {
            $warehouse = Warehouse::create(array_merge($data, [
                'correlation_id' => $correlationId,
            ]));

            $this->audit->record(
                action: 'warehouse_created',
                subjectType: 'warehouse',
                subjectId: $warehouse->id,
                newValues: $data,
                correlationId: $correlationId,
            );

            return $warehouse;
        });
    }

    /**
     * Найти ближайший склад к точке доставки.
     *
     * Использует формулу Haversine для сортировки по расстоянию.
     *
     * @param array{lat: float, lon: float} $deliveryLocation
     */
    public function findNearestWarehouse(array $deliveryLocation, ?string $vertical = null): ?Warehouse
    {
        $lat = $deliveryLocation['lat'];
        $lon = $deliveryLocation['lon'];

        $query = Warehouse::where('is_active', true)
            ->selectRaw(
                '*, ( 6371 * acos( cos( radians(?) ) * cos( radians(lat) ) * cos( radians(lon) - radians(?) ) + sin( radians(?) ) * sin( radians(lat) ) ) ) AS distance',
                [$lat, $lon, $lat],
            )
            ->orderBy('distance');

        return $query->first();
    }

    /**
     * Список складов tenant'а.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Warehouse>
     */
    public function listForTenant(int $tenantId): \Illuminate\Database\Eloquent\Collection
    {
        return Warehouse::where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get();
    }

    /**
     * Деактивировать склад.
     */
    public function deactivate(int $warehouseId, string $correlationId): Warehouse
    {
        return $this->db->transaction(function () use ($warehouseId, $correlationId): Warehouse {
            /** @var Warehouse $warehouse */
            $warehouse = Warehouse::findOrFail($warehouseId);
            $warehouse->update(['is_active' => false, 'correlation_id' => $correlationId]);

            $this->audit->record(
                action: 'warehouse_deactivated',
                subjectType: 'warehouse',
                subjectId: $warehouseId,
                oldValues: ['is_active' => true],
                newValues: ['is_active' => false],
                correlationId: $correlationId,
            );

            return $warehouse;
        });
    }
}
