<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Entities;

use Carbon\CarbonInterface;
use Modules\Inventory\Domain\Enums\BatchStatus;

final readonly class InventoryBatch
{
    public function __construct(
        public int $id,
        public int $inventoryItemId,
        public string $batchNumber,
        public CarbonInterface $manufactureDate,
        public CarbonInterface $expiryDate,
        public int $initialQuantity,
        public int $currentQuantity,
        public float $purchasePrice,
        public ?string $storageLocation,
        public BatchStatus $status,
    ) {
    }

    public function isExpired(): bool
    {
        return $this->expiryDate->isPast();
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        return $this->expiryDate->lte(now()->addDays($days));
    }

    public function isUsable(): bool
    {
        return $this->status->isUsable() && !$this->isExpired();
    }

    public function getDaysUntilExpiry(): int
    {
        return now()->diffInDays($this->expiryDate, false);
    }

    public function hasStock(): bool
    {
        return $this->currentQuantity > 0;
    }
}
