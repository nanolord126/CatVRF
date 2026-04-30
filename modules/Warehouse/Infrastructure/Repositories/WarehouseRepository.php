<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Repositories;

use Modules\Warehouse\Domain\Entities\Warehouse;
use Modules\Warehouse\Domain\Repositories\WarehouseRepositoryInterface;
use Modules\Warehouse\Domain\ValueObjects\WarehouseId;
use Modules\Warehouse\Infrastructure\Models\WarehouseModel;
use Illuminate\Database\DatabaseManager;

final class WarehouseRepository implements WarehouseRepositoryInterface
{
    public function __construct(
        private readonly DatabaseManager $db
    ) {}

    public function save(Warehouse $warehouse): void
    {
        WarehouseModel::updateOrCreate(
            ['id' => $warehouse->getId()->toString()],
            $warehouse->toArray()
        );
    }

    public function findById(WarehouseId $id): ?Warehouse
    {
        $model = WarehouseModel::with(['zones', 'inventoryItems'])->find($id->toString());
        return $model?->toDomain();
    }

    public function findByTenantId(string $tenantId): array
    {
        return WarehouseModel::where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get()
            ->map(fn (WarehouseModel $model) => $model->toDomain())
            ->toArray();
    }

    public function findActive(): array
    {
        return WarehouseModel::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (WarehouseModel $model) => $model->toDomain())
            ->toArray();
    }

    public function findByBranchId(string $branchId): array
    {
        return WarehouseModel::where('branch_id', $branchId)
            ->orderBy('name')
            ->get()
            ->map(fn (WarehouseModel $model) => $model->toDomain())
            ->toArray();
    }

    public function findByType(string $warehouseType): array
    {
        return WarehouseModel::where('type', $warehouseType)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (WarehouseModel $model) => $model->toDomain())
            ->toArray();
    }

    public function findAvailableForReceipt(int $minCapacity = 0): array
    {
        return WarehouseModel::where('is_active', true)
            ->where('capacity', '>=', $minCapacity)
            ->whereRaw('(current_stock < capacity)')
            ->orderBy('name')
            ->get()
            ->map(fn (WarehouseModel $model) => $model->toDomain())
            ->toArray();
    }

    public function delete(WarehouseId $id): void
    {
        $this->db->transaction(function () use ($id) {
            $warehouse = WarehouseModel::find($id->toString());
            
            if (!$warehouse) {
                return;
            }

            // Проверяем, что склад пуст перед удалением
            if ($warehouse->current_stock > 0) {
                throw new \RuntimeException('Cannot delete warehouse with stock');
            }

            WarehouseModel::destroy($id->toString());
        });
    }

    public function exists(WarehouseId $id): bool
    {
        return WarehouseModel::where('id', $id->toString())->exists();
    }

    public function existsByTenantAndName(string $tenantId, string $name): bool
    {
        return WarehouseModel::where('tenant_id', $tenantId)
            ->where('name', $name)
            ->exists();
    }

    public function countByTenantId(string $tenantId): int
    {
        return WarehouseModel::where('tenant_id', $tenantId)->count();
    }

    public function countActiveByTenantId(string $tenantId): int
    {
        return WarehouseModel::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->count();
    }

    public function getTotalCapacityByTenantId(string $tenantId): int
    {
        return (int) WarehouseModel::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->sum('capacity');
    }

    public function getTotalStockByTenantId(string $tenantId): int
    {
        return (int) WarehouseModel::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->sum('current_stock');
    }
}
