<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Repositories;

use Modules\Warehouse\Domain\Entities\InventoryCount;
use Modules\Warehouse\Domain\Repositories\InventoryCountRepositoryInterface;
use Modules\Warehouse\Domain\ValueObjects\InventoryCountId;
use Modules\Warehouse\Domain\ValueObjects\WarehouseId;
use Modules\Warehouse\Infrastructure\Models\InventoryCountModel;
use Illuminate\Database\DatabaseManager;

final class InventoryCountRepository implements InventoryCountRepositoryInterface
{
    public function __construct(
        private readonly DatabaseManager $db
    ) {}

    public function save(InventoryCount $inventoryCount): void
    {
        InventoryCountModel::updateOrCreate(
            ['id' => $inventoryCount->getId()->toString()],
            $inventoryCount->toArray()
        );
    }

    public function findById(InventoryCountId $id): ?InventoryCount
    {
        $model = InventoryCountModel::find($id->toString());
        return $model?->toDomain();
    }

    public function findByCountNumber(string $countNumber): ?InventoryCount
    {
        $model = InventoryCountModel::where('count_number', $countNumber)->first();
        return $model?->toDomain();
    }

    public function findByWarehouseId(WarehouseId $warehouseId): array
    {
        return InventoryCountModel::where('warehouse_id', $warehouseId->toString())
            ->get()
            ->map(fn (InventoryCountModel $model) => $model->toDomain())
            ->toArray();
    }

    public function findScheduled(): array
    {
        return InventoryCountModel::where('status', 'scheduled')
            ->get()
            ->map(fn (InventoryCountModel $model) => $model->toDomain())
            ->toArray();
    }

    public function findInProgress(): array
    {
        return InventoryCountModel::where('status', 'in_progress')
            ->get()
            ->map(fn (InventoryCountModel $model) => $model->toDomain())
            ->toArray();
    }

    public function findPendingApproval(): array
    {
        return InventoryCountModel::where('status', 'pending_approval')
            ->get()
            ->map(fn (InventoryCountModel $model) => $model->toDomain())
            ->toArray();
    }

    public function delete(InventoryCountId $id): void
    {
        InventoryCountModel::destroy($id->toString());
    }

    public function exists(InventoryCountId $id): bool
    {
        return InventoryCountModel::where('id', $id->toString())->exists();
    }
}
