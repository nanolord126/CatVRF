<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Entities;

use Carbon\CarbonInterface;
use Modules\Inventory\Domain\Enums\InventoryCategory;
use Modules\Inventory\Domain\Enums\ItemStatus;

final readonly class InventoryItem
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public string $name,
        public string $sku,
        public ?string $barcode,
        public InventoryCategory $category,
        public ?string $batchNumber,
        public ?CarbonInterface $manufactureDate,
        public ?CarbonInterface $expiryDate,
        public ?int $shelfLifeDays,
        public int $quantity,
        public string $unit,
        public float $purchasePrice,
        public float $sellingPrice,
        public ?int $minStockLevel,
        public ?string $storageConditions,
        public bool $isControlled,
        public ItemStatus $status,
    ) {
    }

    public function isExpired(): bool
    {
        if ($this->expiryDate === null) {
            return false;
        }

        return $this->expiryDate->isPast();
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        if ($this->expiryDate === null) {
            return false;
        }

        return $this->expiryDate->lte(now()->addDays($days));
    }

    public function isUsable(): bool
    {
        return $this->status === ItemStatus::ACTIVE || $this->status === ItemStatus::EXPIRING_SOON;
    }

    public function getDaysUntilExpiry(): ?int
    {
        if ($this->expiryDate === null) {
            return null;
        }

        return (int) now()->diffInDays($this->expiryDate, false);
    }
}
