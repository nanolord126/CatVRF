<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Events;

use Modules\Warehouse\Domain\Entities\Batch;
use Modules\Warehouse\Domain\ValueObjects\BatchId;
use Modules\Warehouse\Domain\ValueObjects\WarehouseId;
use Illuminate\Foundation\Events\Dispatchable;

final readonly class BatchExpiringSoon
{
    use Dispatchable;

    public function __construct(
        private Batch $batch,
        private BatchId $batchId,
        private WarehouseId $warehouseId,
        private int $daysUntilExpiry
    ) {}

    public function getBatch(): Batch
    {
        return $this->batch;
    }

    public function getBatchId(): BatchId
    {
        return $this->batchId;
    }

    public function getWarehouseId(): WarehouseId
    {
        return $this->warehouseId;
    }

    public function getDaysUntilExpiry(): int
    {
        return $this->daysUntilExpiry;
    }

    public function toArray(): array
    {
        return [
            'batch' => $this->batch->toArray(),
            'batch_id' => $this->batchId->toString(),
            'warehouse_id' => $this->warehouseId->toString(),
            'days_until_expiry' => $this->daysUntilExpiry,
        ];
    }
}
