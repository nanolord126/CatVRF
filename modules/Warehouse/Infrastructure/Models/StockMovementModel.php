<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Warehouse\Domain\Entities\StockMovement as StockMovementEntity;
use Modules\Warehouse\Domain\ValueObjects\WarehouseId;
use Modules\Warehouse\Domain\ValueObjects\ZoneId;
use Modules\Warehouse\Domain\ValueObjects\InventoryItemId;
use Modules\Warehouse\Domain\Enums\MovementTypeEnum;
use Modules\Warehouse\Domain\Enums\OrderTypeEnum;

final class StockMovementModel extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'warehouse_stock_movements';

    protected $fillable = [
        'id',
        'warehouse_id',
        'from_zone_id',
        'to_zone_id',
        'inventory_item_id',
        'product_sku',
        'quantity',
        'movement_type',
        'order_type',
        'order_id',
        'branch_id',
        'reason',
        'metadata',
        'correlation_id',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'metadata' => 'array',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(WarehouseModel::class, 'warehouse_id');
    }

    public function fromZone(): BelongsTo
    {
        return $this->belongsTo(WarehouseZoneModel::class, 'from_zone_id');
    }

    public function toZone(): BelongsTo
    {
        return $this->belongsTo(WarehouseZoneModel::class, 'to_zone_id');
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItemModel::class, 'inventory_item_id');
    }

    public function scopeForWarehouse($query, string $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function scopeByMovementType($query, MovementTypeEnum $type)
    {
        return $query->where('movement_type', $type->value);
    }

    public function scopeByOrderType($query, OrderTypeEnum $orderType)
    {
        return $query->where('order_type', $orderType->value);
    }

    public function scopeForBranch($query, string $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeByOrder($query, string $orderId)
    {
        return $query->where('order_id', $orderId);
    }

    public function toDomain(): StockMovementEntity
    {
        return StockMovementEntity::create(
            warehouseId: WarehouseId::fromString($this->warehouse_id),
            fromZoneId: $this->from_zone_id ? ZoneId::fromString($this->from_zone_id) : null,
            toZoneId: $this->to_zone_id ? ZoneId::fromString($this->to_zone_id) : null,
            inventoryItemId: InventoryItemId::fromString($this->inventory_item_id),
            productSku: $this->product_sku,
            quantity: $this->quantity,
            movementType: MovementTypeEnum::from($this->movement_type),
            orderType: $this->order_type ? OrderTypeEnum::from($this->order_type) : null,
            orderId: $this->order_id,
            branchId: $this->branch_id,
            reason: $this->reason
        );
    }

    public static function fromDomain(StockMovementEntity $entity): array
    {
        return [
            'id' => $entity->getId(),
            'warehouse_id' => $entity->getWarehouseId()->toString(),
            'from_zone_id' => $entity->getFromZoneId()?->toString(),
            'to_zone_id' => $entity->getToZoneId()?->toString(),
            'inventory_item_id' => $entity->getInventoryItemId()->toString(),
            'product_sku' => $entity->getProductSku(),
            'quantity' => $entity->getQuantity(),
            'movement_type' => $entity->getMovementType()->value,
            'order_type' => $entity->getOrderType()?->value,
            'order_id' => $entity->getOrderId(),
            'branch_id' => $entity->getBranchId(),
            'reason' => $entity->getReason(),
        ];
    }
}
