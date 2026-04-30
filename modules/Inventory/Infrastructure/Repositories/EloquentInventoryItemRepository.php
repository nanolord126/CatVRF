<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Repositories;

use Modules\Inventory\Domain\Entities\InventoryItem;
use Modules\Inventory\Domain\Repositories\InventoryItemRepositoryInterface;
use Modules\Inventory\Infrastructure\Models\InventoryItemModel;
use Illuminate\Support\Collection;
use Carbon\Carbon;

/**
 * Eloquent Implementation of Inventory Item Repository
 *
 * @author CatVRF Team
 * @version 2026.04.30
 */
final readonly class EloquentInventoryItemRepository implements InventoryItemRepositoryInterface
{
    public function findById(int $id): ?InventoryItem
    {
        $model = InventoryItemModel::find($id);
        return $model?->toDomain();
    }

    public function findBySku(string $sku): ?InventoryItem
    {
        $model = InventoryItemModel::where('sku', $sku)->first();
        return $model?->toDomain();
    }

    public function findByBarcode(string $barcode): ?InventoryItem
    {
        $model = InventoryItemModel::where('barcode', $barcode)->first();
        return $model?->toDomain();
    }

    public function findByTenant(int $tenantId): Collection
    {
        return InventoryItemModel::where('tenant_id', $tenantId)
            ->get()
            ->map(fn (InventoryItemModel $model) => $model->toDomain());
    }

    public function findByCategory(int $tenantId, string $category): Collection
    {
        return InventoryItemModel::where('tenant_id', $tenantId)
            ->where('category', $category)
            ->get()
            ->map(fn (InventoryItemModel $model) => $model->toDomain());
    }

    public function findExpiringSoon(int $tenantId, int $daysWithin = 30): Collection
    {
        return InventoryItemModel::where('tenant_id', $tenantId)
            ->where('expiry_date', '<=', Carbon::now()->addDays($daysWithin))
            ->where('expiry_date', '>', Carbon::now())
            ->get()
            ->map(fn (InventoryItemModel $model) => $model->toDomain());
    }

    public function findExpired(int $tenantId): Collection
    {
        return InventoryItemModel::where('tenant_id', $tenantId)
            ->where('expiry_date', '<', Carbon::now())
            ->get()
            ->map(fn (InventoryItemModel $model) => $model->toDomain());
    }

    public function findLowStock(int $tenantId): Collection
    {
        return InventoryItemModel::where('tenant_id', $tenantId)
            ->whereColumn('quantity', '<=', 'min_stock_level')
            ->get()
            ->map(fn (InventoryItemModel $model) => $model->toDomain());
    }

    public function save(InventoryItem $item): void
    {
        $model = InventoryItemModel::find($item->id);
        
        if ($model === null) {
            InventoryItemModel::create([
                'tenant_id' => $item->tenantId,
                'warehouse_id' => $item->warehouseId ?? null,
                'business_group_id' => $item->businessGroupId ?? null,
                'name' => $item->name,
                'sku' => $item->sku,
                'barcode' => $item->barcode ?? null,
                'category' => $item->category->value,
                'batch_number' => $item->batchNumber ?? null,
                'manufacture_date' => $item->manufactureDate ?? null,
                'expiry_date' => $item->expiryDate ?? null,
                'shelf_life_days' => $item->shelfLifeDays ?? null,
                'quantity' => $item->quantity,
                'reserved' => $item->reserved ?? 0,
                'unit' => $item->unit,
                'purchase_price' => $item->purchasePrice ?? null,
                'selling_price' => $item->sellingPrice ?? null,
                'min_stock_level' => $item->minStockLevel ?? null,
                'storage_conditions' => $item->storageConditions ?? null,
                'storage_location' => $item->storageLocation ?? null,
                'is_controlled' => $item->isControlled,
                'status' => $item->status->value,
                'metadata' => $item->metadata ?? null,
                'correlation_id' => $item->correlationId ?? null,
            ]);
        } else {
            $model->update([
                'warehouse_id' => $item->warehouseId ?? $model->warehouse_id,
                'business_group_id' => $item->businessGroupId ?? $model->business_group_id,
                'name' => $item->name,
                'sku' => $item->sku,
                'barcode' => $item->barcode ?? $model->barcode,
                'category' => $item->category->value,
                'batch_number' => $item->batchNumber ?? $model->batch_number,
                'manufacture_date' => $item->manufactureDate ?? $model->manufacture_date,
                'expiry_date' => $item->expiryDate ?? $model->expiry_date,
                'shelf_life_days' => $item->shelfLifeDays ?? $model->shelf_life_days,
                'quantity' => $item->quantity,
                'reserved' => $item->reserved ?? $model->reserved,
                'unit' => $item->unit,
                'purchase_price' => $item->purchasePrice ?? $model->purchase_price,
                'selling_price' => $item->sellingPrice ?? $model->selling_price,
                'min_stock_level' => $item->minStockLevel ?? $model->min_stock_level,
                'storage_conditions' => $item->storageConditions ?? $model->storage_conditions,
                'storage_location' => $item->storageLocation ?? $model->storage_location,
                'is_controlled' => $item->isControlled,
                'status' => $item->status->value,
                'metadata' => $item->metadata ?? $model->metadata,
                'correlation_id' => $item->correlationId ?? $model->correlation_id,
            ]);
        }
    }

    public function delete(int $id): void
    {
        InventoryItemModel::destroy($id);
    }
}
