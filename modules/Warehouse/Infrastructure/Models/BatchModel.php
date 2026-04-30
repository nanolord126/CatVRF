<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Warehouse\Domain\Entities\Batch as BatchEntity;
use Modules\Warehouse\Domain\ValueObjects\BatchId;
use Modules\Warehouse\Domain\ValueObjects\ProductId;
use Modules\Warehouse\Domain\ValueObjects\WarehouseId;
use Modules\Warehouse\Domain\ValueObjects\ZoneId;
use Modules\Warehouse\Domain\ValueObjects\BinId;

final class BatchModel extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'warehouse_batches';

    protected $fillable = [
        'id',
        'product_id',
        'product_sku',
        'batch_number',
        'lot_number',
        'manufacture_date',
        'expiry_date',
        'initial_quantity',
        'current_quantity',
        'purchase_price',
        'warehouse_id',
        'zone_id',
        'bin_id',
        'supplier_id',
        'supplier_name',
        'certificate_number',
        'status',
        'notes',
        'metadata',
        'correlation_id',
    ];

    protected $casts = [
        'manufacture_date' => 'date',
        'expiry_date' => 'date',
        'initial_quantity' => 'integer',
        'current_quantity' => 'integer',
        'purchase_price' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductModel::class, 'product_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(WarehouseModel::class, 'warehouse_id');
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(WarehouseZoneModel::class, 'zone_id');
    }

    public function bin(): BelongsTo
    {
        return $this->belongsTo(BinModel::class, 'bin_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByProduct($query, string $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeByProductSku($query, string $sku)
    {
        return $query->where('product_sku', $sku);
    }

    public function scopeByBatchNumber($query, string $batchNumber)
    {
        return $query->where('batch_number', $batchNumber);
    }

    public function scopeByWarehouse($query, string $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->where('expiry_date', '<=', now()->addDays($days))
            ->where('expiry_date', '>=', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('expiry_date', '<', now());
    }

    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        return $this->expiry_date && $this->expiry_date->lte(now()->addDays($days));
    }

    public function getDaysUntilExpiry(): ?int
    {
        if (!$this->expiry_date) {
            return null;
        }

        return (int) now()->diffInDays($this->expiry_date, false);
    }

    public function toDomain(): BatchEntity
    {
        return new BatchEntity(
            id: BatchId::fromString($this->id),
            productId: ProductId::fromString($this->product_id),
            productSku: $this->product_sku,
            batchNumber: $this->batch_number,
            lotNumber: $this->lot_number,
            manufactureDate: new \DateTimeImmutable($this->manufacture_date),
            expiryDate: new \DateTimeImmutable($this->expiry_date),
            initialQuantity: $this->initial_quantity,
            currentQuantity: $this->current_quantity,
            purchasePrice: (float) $this->purchase_price,
            warehouseId: WarehouseId::fromString($this->warehouse_id),
            zoneId: $this->zone_id ? ZoneId::fromString($this->zone_id) : null,
            binId: $this->bin_id ? BinId::fromString($this->bin_id) : null,
            supplierId: $this->supplier_id,
            supplierName: $this->supplier_name,
            certificateNumber: $this->certificate_number,
            status: $this->status,
            notes: $this->notes,
            createdAt: new \DateTimeImmutable($this->created_at),
            updatedAt: $this->updated_at ? new \DateTimeImmutable($this->updated_at) : null
        );
    }

    public static function fromDomain(BatchEntity $entity): array
    {
        return [
            'id' => $entity->getId()->toString(),
            'product_id' => $entity->getProductId()->toString(),
            'product_sku' => $entity->getProductSku(),
            'batch_number' => $entity->getBatchNumber(),
            'lot_number' => $entity->getLotNumber(),
            'manufacture_date' => $entity->getManufactureDate()->format('Y-m-d'),
            'expiry_date' => $entity->getExpiryDate()->format('Y-m-d'),
            'initial_quantity' => $entity->getInitialQuantity(),
            'current_quantity' => $entity->getCurrentQuantity(),
            'purchase_price' => $entity->getPurchasePrice(),
            'warehouse_id' => $entity->getWarehouseId()->toString(),
            'zone_id' => $entity->getZoneId()?->toString(),
            'bin_id' => $entity->getBinId()?->toString(),
            'supplier_id' => $entity->getSupplierId(),
            'supplier_name' => $entity->getSupplierName(),
            'certificate_number' => $entity->getCertificateNumber(),
            'status' => $entity->getStatus(),
            'notes' => $entity->getNotes(),
        ];
    }
}
