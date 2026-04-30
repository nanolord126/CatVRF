<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Warehouse\Domain\Entities\WarehouseZone as WarehouseZoneEntity;
use Modules\Warehouse\Domain\ValueObjects\ZoneId;
use Modules\Warehouse\Domain\ValueObjects\WarehouseId;
use Modules\Warehouse\Domain\Enums\ZoneTypeEnum;

final class WarehouseZoneModel extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'warehouse_zones';

    protected $fillable = [
        'id',
        'warehouse_id',
        'name',
        'type',
        'capacity',
        'current_stock',
        'branch_id',
        'is_active',
        'metadata',
        'correlation_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
        'capacity' => 'integer',
        'current_stock' => 'integer',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(WarehouseModel::class, 'warehouse_id');
    }

    public function bins(): HasMany
    {
        return $this->hasMany(BinModel::class, 'zone_id');
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItemModel::class, 'zone_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForWarehouse($query, string $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function scopeForBranch($query, string $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeByType($query, ZoneTypeEnum $type)
    {
        return $query->where('type', $type->value);
    }

    public function getUtilizationPercentage(): float
    {
        if ($this->capacity === 0) {
            return 0.0;
        }

        return ($this->current_stock / $this->capacity) * 100;
    }

    public function toDomain(): WarehouseZoneEntity
    {
        return new WarehouseZoneEntity(
            id: ZoneId::fromString($this->id),
            warehouseId: WarehouseId::fromString($this->warehouse_id),
            name: $this->name,
            type: ZoneTypeEnum::from($this->type),
            capacity: $this->capacity,
            currentStock: $this->current_stock,
            branchId: $this->branch_id,
            isActive: $this->is_active,
            createdAt: new \DateTimeImmutable($this->created_at),
            updatedAt: $this->updated_at ? new \DateTimeImmutable($this->updated_at) : null
        );
    }

    public static function fromDomain(WarehouseZoneEntity $entity): array
    {
        return [
            'id' => $entity->getId()->toString(),
            'warehouse_id' => $entity->getWarehouseId()->toString(),
            'name' => $entity->getName(),
            'type' => $entity->getType()->value,
            'capacity' => $entity->getCapacity(),
            'current_stock' => $entity->getCurrentStock(),
            'branch_id' => $entity->getBranchId(),
            'is_active' => $entity->isActive(),
        ];
    }
}
