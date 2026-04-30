<?php

declare(strict_types=1);

namespace Modules\Restaurant\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Restaurant\Domain\Entities\KitchenStation;
use Modules\Restaurant\Domain\Enums\KitchenStationType;
use Modules\Restaurant\Domain\Repositories\KitchenStationRepositoryInterface;

final class KitchenStationModel extends Model
{
    protected $table = 'kitchen_stations';

    protected $fillable = [
        'tenant_id',
        'name',
        'type',
        'is_active',
        'description',
        'display_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'display_order' => 'integer',
        'type' => KitchenStationType::class,
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (function_exists('tenant') && tenant() !== null) {
                $query->where('kitchen_stations.tenant_id', tenant()->id);
            }
        });
    }

    public function orderStatuses(): HasMany
    {
        return $this->hasMany(OrderKitchenStatusModel::class, 'kitchen_station_id');
    }

    public function toDomain(): KitchenStation
    {
        return new KitchenStation(
            id: $this->id,
            tenantId: $this->tenant_id,
            name: $this->name,
            type: $this->type,
            isActive: $this->is_active,
            description: $this->description,
            displayOrder: $this->display_order,
            createdAt: $this->created_at->toImmutable(),
            updatedAt: $this->updated_at->toImmutable(),
        );
    }

    public static function fromDomain(KitchenStation $station): self
    {
        return new self([
            'id' => $station->id > 0 ? $station->id : null,
            'tenant_id' => $station->tenantId,
            'name' => $station->name,
            'type' => $station->type,
            'is_active' => $station->isActive,
            'description' => $station->description,
            'display_order' => $station->displayOrder,
        ]);
    }
}
