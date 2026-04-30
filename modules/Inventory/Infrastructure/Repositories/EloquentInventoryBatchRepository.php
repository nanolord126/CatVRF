<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Repositories;

use Modules\Inventory\Domain\Entities\InventoryBatch;
use Modules\Inventory\Domain\Repositories\InventoryBatchRepositoryInterface;
use Modules\Inventory\Infrastructure\Models\InventoryBatchModel;
use Illuminate\Support\Collection;
use Carbon\Carbon;

/**
 * Eloquent Implementation of Inventory Batch Repository
 *
 * @author CatVRF Team
 * @version 2026.04.30
 */
final readonly class EloquentInventoryBatchRepository implements InventoryBatchRepositoryInterface
{
    public function findById(int $id): ?InventoryBatch
    {
        $model = InventoryBatchModel::find($id);
        return $model?->toDomain();
    }

    public function findByBatchNumber(int $inventoryItemId, string $batchNumber): ?InventoryBatch
    {
        $model = InventoryBatchModel::where('inventory_item_id', $inventoryItemId)
            ->where('batch_number', $batchNumber)
            ->first();
        return $model?->toDomain();
    }

    public function findByInventoryItem(int $inventoryItemId): Collection
    {
        return InventoryBatchModel::where('inventory_item_id', $inventoryItemId)
            ->get()
            ->map(fn (InventoryBatchModel $model) => $model->toDomain());
    }

    public function findByTenant(int $tenantId): Collection
    {
        return InventoryBatchModel::where('tenant_id', $tenantId)
            ->get()
            ->map(fn (InventoryBatchModel $model) => $model->toDomain());
    }

    public function getNextExpiringBatch(int $inventoryItemId): ?InventoryBatch
    {
        $model = InventoryBatchModel::where('inventory_item_id', $inventoryItemId)
            ->where('status', 'active')
            ->where('current_quantity', '>', 0)
            ->orderBy('expiry_date', 'asc')
            ->first();
        return $model?->toDomain();
    }

    public function getUsableBatches(int $inventoryItemId): Collection
    {
        return InventoryBatchModel::where('inventory_item_id', $inventoryItemId)
            ->where('status', 'active')
            ->where('current_quantity', '>', 0)
            ->where(function ($query) {
                $query->whereNull('expiry_date')
                    ->orWhere('expiry_date', '>', Carbon::now());
            })
            ->orderBy('expiry_date', 'asc')
            ->get()
            ->map(fn (InventoryBatchModel $model) => $model->toDomain());
    }

    public function findExpiringSoon(int $tenantId, int $daysWithin = 30): Collection
    {
        return InventoryBatchModel::where('tenant_id', $tenantId)
            ->where('expiry_date', '<=', Carbon::now()->addDays($daysWithin))
            ->where('expiry_date', '>', Carbon::now())
            ->get()
            ->map(fn (InventoryBatchModel $model) => $model->toDomain());
    }

    public function findExpired(int $tenantId): Collection
    {
        return InventoryBatchModel::where('tenant_id', $tenantId)
            ->where('expiry_date', '<', Carbon::now())
            ->get()
            ->map(fn (InventoryBatchModel $model) => $model->toDomain());
    }

    public function save(InventoryBatch $batch): void
    {
        $model = InventoryBatchModel::find($batch->id);
        
        if ($model === null) {
            InventoryBatchModel::create([
                'tenant_id' => $batch->tenantId,
                'inventory_item_id' => $batch->inventoryItemId,
                'batch_number' => $batch->batchNumber,
                'manufacture_date' => $batch->manufactureDate ?? null,
                'expiry_date' => $batch->expiryDate ?? null,
                'initial_quantity' => $batch->initialQuantity,
                'current_quantity' => $batch->currentQuantity,
                'cost_per_unit' => $batch->costPerUnit ?? null,
                'supplier' => $batch->supplier ?? null,
                'status' => $batch->status->value,
                'metadata' => $batch->metadata ?? null,
                'correlation_id' => $batch->correlationId ?? null,
            ]);
        } else {
            $model->update([
                'batch_number' => $batch->batchNumber,
                'manufacture_date' => $batch->manufactureDate ?? $model->manufacture_date,
                'expiry_date' => $batch->expiryDate ?? $model->expiry_date,
                'initial_quantity' => $batch->initialQuantity,
                'current_quantity' => $batch->currentQuantity,
                'cost_per_unit' => $batch->costPerUnit ?? $model->cost_per_unit,
                'supplier' => $batch->supplier ?? $model->supplier,
                'status' => $batch->status->value,
                'metadata' => $batch->metadata ?? $model->metadata,
                'correlation_id' => $batch->correlationId ?? $model->correlation_id,
            ]);
        }
    }

    public function delete(int $id): void
    {
        InventoryBatchModel::destroy($id);
    }

    public function updateQuantity(int $batchId, int $newQuantity): void
    {
        InventoryBatchModel::where('id', $batchId)->update([
            'current_quantity' => $newQuantity,
        ]);
    }

    public function updateStatus(int $batchId, string $status): void
    {
        InventoryBatchModel::where('id', $batchId)->update([
            'status' => $status,
        ]);
    }
}
