<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Warehouse\Domain\Entities\Bin as BinEntity;
use Modules\Warehouse\Domain\ValueObjects\BinId;
use Modules\Warehouse\Domain\ValueObjects\ZoneId;
use Modules\Warehouse\Domain\ValueObjects\WarehouseId;

final class BinModel extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'warehouse_bins';

    protected $fillable = [
        'id',
        'warehouse_id',
        'zone_id',
        'code',
        'name',
        'coordinates',
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

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(WarehouseModel::class, 'warehouse_id');
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(WarehouseZoneModel::class, 'zone_id');
    }

    public function batches(): HasMany
    {
        return $this->hasMany(BatchModel::class, 'bin_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForZone($query, string $zoneId)
    {
        return $query->where('zone_id', $zoneId);
    }

    public function scopeForWarehouse($query, string $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function scopeByCode($query, string $code)
    {
        return $query->where('code', $code);
    }

    public function getUtilizationPercentage(): float
    {
        if ($this->capacity === 0) {
            return 0.0;
        }

        return ($this->current_stock / $this->capacity) * 100;
    }

    public function toDomain(): BinEntity
    {
        return new BinEntity(
            id: BinId::fromString($this->id),
            warehouseId: WarehouseId::fromString($this->warehouse_id),
            zoneId: ZoneId::fromString($this->zone_id),
            code: $this->code,
            name: $this->name,
            coordinates: $this->coordinates,
            capacity: $this->capacity,
            currentStock: $this->current_stock,
            isActive: $this->is_active,
            createdAt: new \DateTimeImmutable($this->created_at),
            updatedAt: $this->updated_at ? new \DateTimeImmutable($this->updated_at) : null
        );
    }

    public static function fromDomain(BinEntity $entity): array
    {
        return [
            'id' => $entity->getId()->toString(),
            'warehouse_id' => $entity->getWarehouseId()->toString(),
            'zone_id' => $entity->getZoneId()->toString(),
            'code' => $entity->getCode(),
            'name' => $entity->getName(),
            'coordinates' => $entity->getCoordinates(),
            'capacity' => $entity->getCapacity(),
            'current_stock' => $entity->getCurrentStock(),
            'is_active' => $entity->isActive(),
        ];
    }
}
