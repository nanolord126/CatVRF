<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Warehouse\Domain\Entities\InventoryItem as InventoryItemEntity;
use Modules\Warehouse\Domain\ValueObjects\InventoryItemId;
use Modules\Warehouse\Domain\ValueObjects\WarehouseId;
use Modules\Warehouse\Domain\ValueObjects\ZoneId;
use Modules\Warehouse\Domain\Enums\OrderTypeEnum;

final class InventoryItemModel extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'warehouse_inventory_items';

    protected $fillable = [
        'id',
        'warehouse_id',
        'zone_id',
        'product_sku',
        'product_name',
        'quantity',
        'reserved_quantity',
        'order_type',
        'branch_id',
        'metadata',
        'correlation_id',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'reserved_quantity' => 'integer',
        'metadata' => 'array',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(WarehouseModel::class, 'warehouse_id');
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(WarehouseZoneModel::class, 'zone_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovementModel::class, 'inventory_item_id');
    }

    public function scopeForWarehouse($query, string $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function scopeForBranch($query, string $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeByOrderType($query, OrderTypeEnum $orderType)
    {
        return $query->where('order_type', $orderType->value);
    }

    public function scopeShared($query)
    {
        return $query->whereNull('order_type');
    }

    public function scopeByProductSku($query, string $sku)
    {
        return $query->where('product_sku', $sku);
    }

    public function getAvailableQuantity(): int
    {
        return $this->quantity - $this->reserved_quantity;
    }

    public function toDomain(): InventoryItemEntity
    {
        return new InventoryItemEntity(
            id: InventoryItemId::fromString($this->id),
            warehouseId: WarehouseId::fromString($this->warehouse_id),
            zoneId: $this->zone_id ? ZoneId::fromString($this->zone_id) : null,
            productSku: $this->product_sku,
            productName: $this->product_name,
            quantity: $this->quantity,
            reservedQuantity: $this->reserved_quantity,
            orderType: $this->order_type ? OrderTypeEnum::from($this->order_type) : null,
            branchId: $this->branch_id,
            createdAt: new \DateTimeImmutable($this->created_at),
            updatedAt: $this->updated_at ? new \DateTimeImmutable($this->updated_at) : null
        );
    }

    public static function fromDomain(InventoryItemEntity $entity): array
    {
        return [
            'id' => $entity->getId()->toString(),
            'warehouse_id' => $entity->getWarehouseId()->toString(),
            'zone_id' => $entity->getZoneId()?->toString(),
            'product_sku' => $entity->getProductSku(),
            'product_name' => $entity->getProductName(),
            'quantity' => $entity->getQuantity(),
            'reserved_quantity' => $entity->getReservedQuantity(),
            'order_type' => $entity->getOrderType()?->value,
            'branch_id' => $entity->getBranchId(),
        ];
    }
}
