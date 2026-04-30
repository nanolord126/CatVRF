<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Warehouse\Domain\Entities\Warehouse as WarehouseEntity;
use Modules\Warehouse\Domain\ValueObjects\WarehouseId;
use Modules\Warehouse\Domain\Enums\WarehouseTypeEnum;

final class WarehouseModel extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'warehouses';

    protected $fillable = [
        'id',
        'tenant_id',
        'name',
        'address',
        'branch_id',
        'type',
        'capacity',
        'current_stock',
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

    public function zones(): HasMany
    {
        return $this->hasMany(WarehouseZoneModel::class, 'warehouse_id');
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItemModel::class, 'warehouse_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeForBranch($query, string $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeByType($query, WarehouseTypeEnum $type)
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

    public function toDomain(): WarehouseEntity
    {
        return WarehouseEntity::create(
            name: $this->name,
            address: $this->address,
            branchId: $this->branch_id,
            type: WarehouseTypeEnum::from($this->type),
            capacity: $this->capacity
        );
    }

    public static function fromDomain(WarehouseEntity $entity, int $tenantId): array
    {
        return [
            'id' => $entity->getId()->toString(),
            'tenant_id' => $tenantId,
            'name' => $entity->getName(),
            'address' => $entity->getAddress(),
            'branch_id' => $entity->getBranchId(),
            'type' => $entity->getType()->value,
            'capacity' => $entity->getCapacity(),
            'current_stock' => $entity->getCurrentStock(),
            'is_active' => $entity->isActive(),
        ];
    }
}
