<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Repositories;

use Modules\Warehouse\Domain\Entities\InventoryItem;
use Modules\Warehouse\Domain\Repositories\InventoryItemRepositoryInterface;
use Modules\Warehouse\Domain\ValueObjects\InventoryItemId;
use Modules\Warehouse\Domain\ValueObjects\WarehouseId;
use Modules\Warehouse\Domain\Enums\OrderTypeEnum;
use Modules\Warehouse\Infrastructure\Models\InventoryItemModel;
use Illuminate\Database\DatabaseManager;

final class InventoryItemRepository implements InventoryItemRepositoryInterface
{
    public function __construct(
        private readonly DatabaseManager $db
    ) {}

    public function save(InventoryItem $item): void
    {
        InventoryItemModel::updateOrCreate(
            ['id' => $item->getId()->toString()],
            $item->toArray()
        );
    }

    public function findById(string $id): ?InventoryItem
    {
        $model = InventoryItemModel::find($id);
        return $model?->toDomain();
    }

    public function findByWarehouse(WarehouseId $warehouseId, ?string $branchId = null): array
    {
        $query = InventoryItemModel::where('warehouse_id', $warehouseId->toString());
        
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->get()
            ->map(fn (InventoryItemModel $model) => $model->toDomain())
            ->toArray();
    }

    public function findByWarehouseAndOrderType(
        WarehouseId $warehouseId,
        OrderTypeEnum $orderType,
        ?string $branchId = null
    ): array {
        $query = InventoryItemModel::where('warehouse_id', $warehouseId->toString())
            ->where('order_type', $orderType->value);
        
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->get()
            ->map(fn (InventoryItemModel $model) => $model->toDomain())
            ->toArray();
    }

    public function findSharedByWarehouse(WarehouseId $warehouseId, ?string $branchId = null): array
    {
        $query = InventoryItemModel::where('warehouse_id', $warehouseId->toString())
            ->whereNull('order_type');
        
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->get()
            ->map(fn (InventoryItemModel $model) => $model->toDomain())
            ->toArray();
    }

    public function findByProductSku(string $sku, ?string $branchId = null): array
    {
        $query = InventoryItemModel::where('product_sku', $sku);
        
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->get()
            ->map(fn (InventoryItemModel $model) => $model->toDomain())
            ->toArray();
    }

    public function delete(string $id): void
    {
        InventoryItemModel::destroy($id);
    }
}
