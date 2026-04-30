<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Repositories;

use Modules\Warehouse\Domain\Entities\StockMovement;
use Modules\Warehouse\Domain\Repositories\StockMovementRepositoryInterface;
use Modules\Warehouse\Domain\ValueObjects\WarehouseId;
use Modules\Warehouse\Domain\Enums\OrderTypeEnum;
use Modules\Warehouse\Domain\Enums\MovementTypeEnum;
use Modules\Warehouse\Infrastructure\Models\StockMovementModel;
use Illuminate\Database\DatabaseManager;

final class StockMovementRepository implements StockMovementRepositoryInterface
{
    public function __construct(
        private readonly DatabaseManager $db
    ) {}

    public function save(StockMovement $movement): void
    {
        $data = StockMovementModel::fromDomain($movement);
        
        StockMovementModel::updateOrCreate(
            ['id' => $data['id']],
            $data
        );
    }

    public function findById(string $id): ?StockMovement
    {
        $model = StockMovementModel::find($id);
        return $model?->toDomain();
    }

    public function findByWarehouse(WarehouseId $warehouseId, ?string $branchId = null): array
    {
        $query = StockMovementModel::where('warehouse_id', $warehouseId->toString());
        
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->get()
            ->map(fn (StockMovementModel $model) => $model->toDomain())
            ->toArray();
    }

    public function findByOrderType(OrderTypeEnum $orderType, ?string $branchId = null): array
    {
        $query = StockMovementModel::where('order_type', $orderType->value);
        
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->get()
            ->map(fn (StockMovementModel $model) => $model->toDomain())
            ->toArray();
    }

    public function findByMovementType(MovementTypeEnum $movementType, ?string $branchId = null): array
    {
        $query = StockMovementModel::where('movement_type', $movementType->value);
        
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->get()
            ->map(fn (StockMovementModel $model) => $model->toDomain())
            ->toArray();
    }

    public function findByInventoryItem(string $inventoryItemId): array
    {
        return StockMovementModel::where('inventory_item_id', $inventoryItemId)
            ->get()
            ->map(fn (StockMovementModel $model) => $model->toDomain())
            ->toArray();
    }

    public function findByDateRange(\DateTimeImmutable $from, \DateTimeImmutable $to, ?string $branchId = null): array
    {
        $query = StockMovementModel::whereBetween('created_at', [$from, $to]);
        
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->get()
            ->map(fn (StockMovementModel $model) => $model->toDomain())
            ->toArray();
    }
}
