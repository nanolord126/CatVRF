<?php

declare(strict_types=1);

namespace Modules\Flowers\Domain\Entities;

use Carbon\CarbonImmutable;
use Modules\Flowers\Domain\Enums\FreshnessStatus;

final readonly class Flower
{
    public function __construct(
        public int $id,
        public int $venueId,
        public int $tenantId,
        public string $name,
        public string $slug,
        public string $category,
        public ?string $color,
        public ?string $variety,
        public ?string $description,
        public ?string $supplier,
        public ?string $supplierCode,
        public int $stockQuantity,
        public int $reservedQuantity,
        public int $minimumStock,
        public float $costPrice,
        public float $sellingPrice,
        public string $unit,
        public int $unitsPerBunch,
        public CarbonImmutable $expiryDate,
        public CarbonImmutable $receivedDate,
        public FreshnessStatus $freshnessStatus,
        public ?int $stemLengthCm,
        public ?string $storageConditions,
        public bool $isActive,
        public ?array $metadata,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
        public ?CarbonImmutable $deletedAt,
    ) {}

    public static function create(
        int $venueId,
        int $tenantId,
        string $name,
        string $slug,
        string $category,
        CarbonImmutable $expiryDate,
        float $costPrice,
        float $sellingPrice,
        string $unit = 'stem',
        ?string $color = null,
        ?string $variety = null,
        ?string $description = null,
        ?string $supplier = null,
        ?string $supplierCode = null,
        int $stockQuantity = 0,
        int $minimumStock = 10,
        int $unitsPerBunch = 1,
        ?int $stemLengthCm = null,
        ?string $storageConditions = null,
    ): self {
        return new self(
            id: 0,
            venueId: $venueId,
            tenantId: $tenantId,
            name: $name,
            slug: $slug,
            category: $category,
            color: $color,
            variety: $variety,
            description: $description,
            supplier: $supplier,
            supplierCode: $supplierCode,
            stockQuantity: $stockQuantity,
            reservedQuantity: 0,
            minimumStock: $minimumStock,
            costPrice: $costPrice,
            sellingPrice: $sellingPrice,
            unit: $unit,
            unitsPerBunch: $unitsPerBunch,
            expiryDate: $expiryDate,
            receivedDate: CarbonImmutable::now(),
            freshnessStatus: FreshnessStatus::FRESH,
            stemLengthCm: $stemLengthCm,
            storageConditions: $storageConditions,
            isActive: true,
            metadata: null,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
            deletedAt: null,
        );
    }

    public function getAvailableQuantity(): int
    {
        return max(0, $this->stockQuantity - $this->reservedQuantity);
    }

    public function isLowStock(): bool
    {
        return $this->getAvailableQuantity() <= $this->minimumStock;
    }

    public function isExpired(): bool
    {
        return $this->expiryDate->isPast();
    }

    public function isExpiringWithin(int $days): bool
    {
        return $this->expiryDate->diffInDays(CarbonImmutable::now()) <= $days;
    }

    public function canReserve(int $quantity): bool
    {
        return $this->getAvailableQuantity() >= $quantity && $this->isActive;
    }

    public function reserve(int $quantity): self
    {
        return new self(
            id: $this->id,
            venueId: $this->venueId,
            tenantId: $this->tenantId,
            name: $this->name,
            slug: $this->slug,
            category: $this->category,
            color: $this->color,
            variety: $this->variety,
            description: $this->description,
            supplier: $this->supplier,
            supplierCode: $this->supplierCode,
            stockQuantity: $this->stockQuantity,
            reservedQuantity: $this->reservedQuantity + $quantity,
            minimumStock: $this->minimumStock,
            costPrice: $this->costPrice,
            sellingPrice: $this->sellingPrice,
            unit: $this->unit,
            unitsPerBunch: $this->unitsPerBunch,
            expiryDate: $this->expiryDate,
            receivedDate: $this->receivedDate,
            freshnessStatus: $this->freshnessStatus,
            stemLengthCm: $this->stemLengthCm,
            storageConditions: $this->storageConditions,
            isActive: $this->isActive,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
            deletedAt: $this->deletedAt,
        );
    }

    public function releaseReservation(int $quantity): self
    {
        return new self(
            id: $this->id,
            venueId: $this->venueId,
            tenantId: $this->tenantId,
            name: $this->name,
            slug: $this->slug,
            category: $this->category,
            color: $this->color,
            variety: $this->variety,
            description: $this->description,
            supplier: $this->supplier,
            supplierCode: $this->supplierCode,
            stockQuantity: $this->stockQuantity,
            reservedQuantity: max(0, $this->reservedQuantity - $quantity),
            minimumStock: $this->minimumStock,
            costPrice: $this->costPrice,
            sellingPrice: $this->sellingPrice,
            unit: $this->unit,
            unitsPerBunch: $this->unitsPerBunch,
            expiryDate: $this->expiryDate,
            receivedDate: $this->receivedDate,
            freshnessStatus: $this->freshnessStatus,
            stemLengthCm: $this->stemLengthCm,
            storageConditions: $this->storageConditions,
            isActive: $this->isActive,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
            deletedAt: $this->deletedAt,
        );
    }

    public function consume(int $quantity): self
    {
        return new self(
            id: $this->id,
            venueId: $this->venueId,
            tenantId: $this->tenantId,
            name: $this->name,
            slug: $this->slug,
            category: $this->category,
            color: $this->color,
            variety: $this->variety,
            description: $this->description,
            supplier: $this->supplier,
            supplierCode: $this->supplierCode,
            stockQuantity: max(0, $this->stockQuantity - $quantity),
            reservedQuantity: max(0, $this->reservedQuantity - $quantity),
            minimumStock: $this->minimumStock,
            costPrice: $this->costPrice,
            sellingPrice: $this->sellingPrice,
            unit: $this->unit,
            unitsPerBunch: $this->unitsPerBunch,
            expiryDate: $this->expiryDate,
            receivedDate: $this->receivedDate,
            freshnessStatus: $this->freshnessStatus,
            stemLengthCm: $this->stemLengthCm,
            storageConditions: $this->storageConditions,
            isActive: $this->isActive,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
            deletedAt: $this->deletedAt,
        );
    }

    public function updateFreshnessStatus(FreshnessStatus $status): self
    {
        return new self(
            id: $this->id,
            venueId: $this->venueId,
            tenantId: $this->tenantId,
            name: $this->name,
            slug: $this->slug,
            category: $this->category,
            color: $this->color,
            variety: $this->variety,
            description: $this->description,
            supplier: $this->supplier,
            supplierCode: $this->supplierCode,
            stockQuantity: $this->stockQuantity,
            reservedQuantity: $this->reservedQuantity,
            minimumStock: $this->minimumStock,
            costPrice: $this->costPrice,
            sellingPrice: $this->sellingPrice,
            unit: $this->unit,
            unitsPerBunch: $this->unitsPerBunch,
            expiryDate: $this->expiryDate,
            receivedDate: $this->receivedDate,
            freshnessStatus: $status,
            stemLengthCm: $this->stemLengthCm,
            storageConditions: $this->storageConditions,
            isActive: $this->isActive,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
            deletedAt: $this->deletedAt,
        );
    }
}
