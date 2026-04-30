<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Repositories;

use Modules\Warehouse\Domain\Entities\WarehouseZone;
use Modules\Warehouse\Domain\Repositories\ZoneRepositoryInterface;
use Modules\Warehouse\Domain\ValueObjects\ZoneId;
use Modules\Warehouse\Domain\ValueObjects\WarehouseId;
use Modules\Warehouse\Infrastructure\Models\WarehouseZoneModel;
use Illuminate\Database\DatabaseManager;

final class ZoneRepository implements ZoneRepositoryInterface
{
    public function __construct(
        private readonly DatabaseManager $db
    ) {}

    public function save(WarehouseZone $zone): void
    {
        WarehouseZoneModel::updateOrCreate(
            ['id' => $zone->getId()->toString()],
            $zone->toArray()
        );
    }

    public function findById(ZoneId $id): ?WarehouseZone
    {
        $model = WarehouseZoneModel::with(['warehouse', 'bins'])->find($id->toString());
        return $model?->toDomain();
    }

    public function findByWarehouseId(WarehouseId $warehouseId): array
    {
        return WarehouseZoneModel::where('warehouse_id', $warehouseId->toString())
            ->orderBy('name')
            ->get()
            ->map(fn (WarehouseZoneModel $model) => $model->toDomain())
            ->toArray();
    }

    public function findActiveByWarehouseId(WarehouseId $warehouseId): array
    {
        return WarehouseZoneModel::where('warehouse_id', $warehouseId->toString())
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (WarehouseZoneModel $model) => $model->toDomain())
            ->toArray();
    }

    public function findByType(string $zoneType): array
    {
        return WarehouseZoneModel::where('zone_type', $zoneType)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (WarehouseZoneModel $model) => $model->toDomain())
            ->toArray();
    }

    public function findAvailableZones(WarehouseId $warehouseId, int $minCapacity = 0): array
    {
        return WarehouseZoneModel::where('warehouse_id', $warehouseId->toString())
            ->where('is_active', true)
            ->where('capacity', '>=', $minCapacity)
            ->whereRaw('(current_stock < capacity)')
            ->orderBy('name')
            ->get()
            ->map(fn (WarehouseZoneModel $model) => $model->toDomain())
            ->toArray();
    }

    public function delete(ZoneId $id): void
    {
        $this->db->transaction(function () use ($id) {
            $zone = WarehouseZoneModel::find($id->toString());
            
            if (!$zone) {
                return;
            }

            // Проверяем, что зона пуста перед удалением
            if ($zone->current_stock > 0) {
                throw new \RuntimeException('Cannot delete zone with stock');
            }

            WarehouseZoneModel::destroy($id->toString());
        });
    }

    public function exists(ZoneId $id): bool
    {
        return WarehouseZoneModel::where('id', $id->toString())->exists();
    }

    public function existsByWarehouseAndName(WarehouseId $warehouseId, string $name): bool
    {
        return WarehouseZoneModel::where('warehouse_id', $warehouseId->toString())
            ->where('name', $name)
            ->exists();
    }

    public function countByWarehouseId(WarehouseId $warehouseId): int
    {
        return WarehouseZoneModel::where('warehouse_id', $warehouseId->toString())->count();
    }

    public function countActiveByWarehouseId(WarehouseId $warehouseId): int
    {
        return WarehouseZoneModel::where('warehouse_id', $warehouseId->toString())
            ->where('is_active', true)
            ->count();
    }

    public function getTotalCapacityByWarehouseId(WarehouseId $warehouseId): int
    {
        return (int) WarehouseZoneModel::where('warehouse_id', $warehouseId->toString())
            ->where('is_active', true)
            ->sum('capacity');
    }

    public function getTotalStockByWarehouseId(WarehouseId $warehouseId): int
    {
        return (int) WarehouseZoneModel::where('warehouse_id', $warehouseId->toString())
            ->where('is_active', true)
            ->sum('current_stock');
    }
}
