<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Entities;

use Modules\Warehouse\Domain\ValueObjects\ProductId;
use Modules\Warehouse\Domain\ValueObjects\BatchId;
use Modules\Warehouse\Domain\ValueObjects\WarehouseId;
use Modules\Warehouse\Domain\ValueObjects\ZoneId;
use Modules\Warehouse\Domain\ValueObjects\BinId;

final readonly class Batch
{
    public function __construct(
        private BatchId $id,
        private ProductId $productId,
        private string $productSku,
        private string $batchNumber,
        private string $lotNumber,
        private \DateTimeImmutable $manufactureDate,
        private \DateTimeImmutable $expiryDate,
        private int $initialQuantity,
        private int $currentQuantity,
        private float $purchasePrice,
        private WarehouseId $warehouseId,
        private ?ZoneId $zoneId,
        private ?BinId $binId,
        private ?string $supplierId,
        private ?string $supplierName,
        private ?string $certificateNumber,
        private string $status,
        private ?string $notes,
        private \DateTimeImmutable $createdAt,
        private ?\DateTimeImmutable $updatedAt = null
    ) {
        if ($this->currentQuantity > $this->initialQuantity) {
            throw new \InvalidArgumentException('Current quantity cannot exceed initial quantity');
        }

        if ($this->currentQuantity < 0) {
            throw new \InvalidArgumentException('Current quantity cannot be negative');
        }
    }

    public static function create(
        ProductId $productId,
        string $productSku,
        string $batchNumber,
        string $lotNumber,
        \DateTimeImmutable $manufactureDate,
        \DateTimeImmutable $expiryDate,
        int $initialQuantity,
        float $purchasePrice,
        WarehouseId $warehouseId,
        ?string $supplierId = null,
        ?string $supplierName = null,
        ?string $certificateNumber = null
    ): self {
        return new self(
            id: BatchId::generate(),
            productId: $productId,
            productSku: $productSku,
            batchNumber: $batchNumber,
            lotNumber: $lotNumber,
            manufactureDate: $manufactureDate,
            expiryDate: $expiryDate,
            initialQuantity: $initialQuantity,
            currentQuantity: $initialQuantity,
            purchasePrice: $purchasePrice,
            warehouseId: $warehouseId,
            zoneId: null,
            binId: null,
            supplierId: $supplierId,
            supplierName: $supplierName,
            certificateNumber: $certificateNumber,
            status: 'active',
            notes: null,
            createdAt: new \DateTimeImmutable()
        );
    }

    public function getId(): BatchId
    {
        return $this->id;
    }

    public function getProductId(): ProductId
    {
        return $this->productId;
    }

    public function getProductSku(): string
    {
        return $this->productSku;
    }

    public function getBatchNumber(): string
    {
        return $this->batchNumber;
    }

    public function getLotNumber(): string
    {
        return $this->lotNumber;
    }

    public function getManufactureDate(): \DateTimeImmutable
    {
        return $this->manufactureDate;
    }

    public function getExpiryDate(): \DateTimeImmutable
    {
        return $this->expiryDate;
    }

    public function getInitialQuantity(): int
    {
        return $this->initialQuantity;
    }

    public function getCurrentQuantity(): int
    {
        return $this->currentQuantity;
    }

    public function getPurchasePrice(): float
    {
        return $this->purchasePrice;
    }

    public function getWarehouseId(): WarehouseId
    {
        return $this->warehouseId;
    }

    public function getZoneId(): ?ZoneId
    {
        return $this->zoneId;
    }

    public function getBinId(): ?BinId
    {
        return $this->binId;
    }

    public function getSupplierId(): ?string
    {
        return $this->supplierId;
    }

    public function getSupplierName(): ?string
    {
        return $this->supplierName;
    }

    public function getCertificateNumber(): ?string
    {
        return $this->certificateNumber;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function isExpired(): bool
    {
        return $this->expiryDate < new \DateTimeImmutable();
    }

    public function getDaysUntilExpiry(): int
    {
        $today = new \DateTimeImmutable();
        $interval = $today->diff($this->expiryDate);
        return (int) $interval->format('%r%a');
    }

    public function isExpiringSoon(int $daysThreshold = 30): bool
    {
        return $this->getDaysUntilExpiry() <= $daysThreshold && $this->getDaysUntilExpiry() > 0;
    }

    public function deductQuantity(int $quantity): self
    {
        $newQuantity = $this->currentQuantity - $quantity;

        if ($newQuantity < 0) {
            throw new \InvalidArgumentException('Cannot deduct more than current quantity');
        }

        return new self(
            id: $this->id,
            productId: $this->productId,
            productSku: $this->productSku,
            batchNumber: $this->batchNumber,
            lotNumber: $this->lotNumber,
            manufactureDate: $this->manufactureDate,
            expiryDate: $this->expiryDate,
            initialQuantity: $this->initialQuantity,
            currentQuantity: $newQuantity,
            purchasePrice: $this->purchasePrice,
            warehouseId: $this->warehouseId,
            zoneId: $this->zoneId,
            binId: $this->binId,
            supplierId: $this->supplierId,
            supplierName: $this->supplierName,
            certificateNumber: $this->certificateNumber,
            status: $newQuantity === 0 ? 'depleted' : $this->status,
            notes: $this->notes,
            createdAt: $this->createdAt,
            updatedAt: new \DateTimeImmutable()
        );
    }

    public function setLocation(?ZoneId $zoneId, ?BinId $binId): self
    {
        return new self(
            id: $this->id,
            productId: $this->productId,
            productSku: $this->productSku,
            batchNumber: $this->batchNumber,
            lotNumber: $this->lotNumber,
            manufactureDate: $this->manufactureDate,
            expiryDate: $this->expiryDate,
            initialQuantity: $this->initialQuantity,
            currentQuantity: $this->currentQuantity,
            purchasePrice: $this->purchasePrice,
            warehouseId: $this->warehouseId,
            zoneId: $zoneId,
            binId: $binId,
            supplierId: $this->supplierId,
            supplierName: $this->supplierName,
            certificateNumber: $this->certificateNumber,
            status: $this->status,
            notes: $this->notes,
            createdAt: $this->createdAt,
            updatedAt: new \DateTimeImmutable()
        );
    }

    public function markAsExpired(): self
    {
        return new self(
            id: $this->id,
            productId: $this->productId,
            productSku: $this->productSku,
            batchNumber: $this->batchNumber,
            lotNumber: $this->lotNumber,
            manufactureDate: $this->manufactureDate,
            expiryDate: $this->expiryDate,
            initialQuantity: $this->initialQuantity,
            currentQuantity: $this->currentQuantity,
            purchasePrice: $this->purchasePrice,
            warehouseId: $this->warehouseId,
            zoneId: $this->zoneId,
            binId: $this->binId,
            supplierId: $this->supplierId,
            supplierName: $this->supplierName,
            certificateNumber: $this->certificateNumber,
            status: 'expired',
            notes: $this->notes,
            createdAt: $this->createdAt,
            updatedAt: new \DateTimeImmutable()
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id->toString(),
            'product_id' => $this->productId->toString(),
            'product_sku' => $this->productSku,
            'batch_number' => $this->batchNumber,
            'lot_number' => $this->lotNumber,
            'manufacture_date' => $this->manufactureDate->format('Y-m-d'),
            'expiry_date' => $this->expiryDate->format('Y-m-d'),
            'initial_quantity' => $this->initialQuantity,
            'current_quantity' => $this->currentQuantity,
            'purchase_price' => $this->purchasePrice,
            'warehouse_id' => $this->warehouseId->toString(),
            'zone_id' => $this->zoneId?->toString(),
            'bin_id' => $this->binId?->toString(),
            'supplier_id' => $this->supplierId,
            'supplier_name' => $this->supplierName,
            'certificate_number' => $this->certificateNumber,
            'status' => $this->status,
            'notes' => $this->notes,
            'is_expired' => $this->isExpired(),
            'days_until_expiry' => $this->getDaysUntilExpiry(),
            'is_expiring_soon' => $this->isExpiringSoon(),
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
