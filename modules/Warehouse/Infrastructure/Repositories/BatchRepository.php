<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Repositories;

use Modules\Warehouse\Domain\Entities\Batch;
use Modules\Warehouse\Domain\Repositories\BatchRepositoryInterface;
use Modules\Warehouse\Domain\ValueObjects\BatchId;
use Modules\Warehouse\Domain\ValueObjects\ProductId;
use Modules\Warehouse\Domain\ValueObjects\WarehouseId;
use Modules\Warehouse\Infrastructure\Models\BatchModel;
use Illuminate\Database\DatabaseManager;
use Carbon\Carbon;

final class BatchRepository implements BatchRepositoryInterface
{
    public function __construct(
        private readonly DatabaseManager $db
    ) {}

    public function save(Batch $batch): void
    {
        BatchModel::updateOrCreate(
            ['id' => $batch->getId()->toString()],
            $batch->toArray()
        );
    }

    public function findById(BatchId $id): ?Batch
    {
        $model = BatchModel::find($id->toString());
        return $model?->toDomain();
    }

    public function findByBatchNumber(string $batchNumber): ?Batch
    {
        $model = BatchModel::where('batch_number', $batchNumber)->first();
        return $model?->toDomain();
    }

    public function findByProductId(ProductId $productId): array
    {
        return BatchModel::where('product_id', $productId->toString())
            ->get()
            ->map(fn (BatchModel $model) => $model->toDomain())
            ->toArray();
    }

    public function findByProductSku(string $productSku): array
    {
        return BatchModel::where('product_sku', $productSku)
            ->get()
            ->map(fn (BatchModel $model) => $model->toDomain())
            ->toArray();
    }

    public function findActiveByProductSku(string $productSku): array
    {
        return BatchModel::where('product_sku', $productSku)
            ->where('current_quantity', '>', 0)
            ->where('status', 'active')
            ->get()
            ->map(fn (BatchModel $model) => $model->toDomain())
            ->toArray();
    }

    public function findByWarehouseId(WarehouseId $warehouseId): array
    {
        return BatchModel::where('warehouse_id', $warehouseId->toString())
            ->get()
            ->map(fn (BatchModel $model) => $model->toDomain())
            ->toArray();
    }

    public function findExpiringBatches(int $daysThreshold = 30): array
    {
        $thresholdDate = Carbon::now()->addDays($daysThreshold);
        
        return BatchModel::where('expiry_date', '<=', $thresholdDate)
            ->where('expiry_date', '>', Carbon::now())
            ->where('current_quantity', '>', 0)
            ->get()
            ->map(fn (BatchModel $model) => $model->toDomain())
            ->toArray();
    }

    public function findExpiredBatches(): array
    {
        return BatchModel::where('expiry_date', '<', Carbon::now())
            ->where('current_quantity', '>', 0)
            ->get()
            ->map(fn (BatchModel $model) => $model->toDomain())
            ->toArray();
    }

    public function delete(BatchId $id): void
    {
        BatchModel::destroy($id->toString());
    }

    public function exists(BatchId $id): bool
    {
        return BatchModel::where('id', $id->toString())->exists();
    }
}
