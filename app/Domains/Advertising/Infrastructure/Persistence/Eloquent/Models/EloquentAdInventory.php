<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Infrastructure\Persistence\Eloquent\Models;

use App\Domains\Advertising\Domain\Entities\AdInventory as AdInventoryEntity;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Eloquent AdInventory Model
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise
 */
class EloquentAdInventory extends Model
{
    use SoftDeletes;

    protected $table = 'ad_inventory';

    protected $fillable = [
        'uuid',
        'publisher_id',
        'inventory_type',
        'placement',
        'available_impressions',
        'reserved_impressions',
        'available_from',
        'available_until',
        'status',
        'targeting_restrictions',
        'correlation_id',
    ];

    protected $casts = [
        'publisher_id' => 'integer',
        'available_impressions' => 'integer',
        'reserved_impressions' => 'integer',
        'available_from' => 'datetime',
        'available_until' => 'datetime',
        'targeting_restrictions' => 'array',
    ];

    public function toDomain(): AdInventoryEntity
    {
        return new AdInventoryEntity(
            id: $this->id,
            uuid: $this->uuid,
            publisher_id: $this->publisher_id,
            inventory_type: $this->inventory_type,
            placement: $this->placement,
            available_impressions: $this->available_impressions,
            reserved_impressions: $this->reserved_impressions,
            available_from: $this->available_from,
            available_until: $this->available_until,
            status: $this->status,
            targeting_restrictions: $this->targeting_restrictions ?? [],
            correlation_id: $this->correlation_id,
        );
    }

    public static function fromDomain(AdInventoryEntity $entity): self
    {
        return new self([
            'uuid' => $entity->uuid,
            'publisher_id' => $entity->publisher_id,
            'inventory_type' => $entity->inventory_type,
            'placement' => $entity->placement,
            'available_impressions' => $entity->available_impressions,
            'reserved_impressions' => $entity->reserved_impressions,
            'available_from' => $entity->available_from,
            'available_until' => $entity->available_until,
            'status' => $entity->status,
            'targeting_restrictions' => $entity->targeting_restrictions,
            'correlation_id' => $entity->correlation_id,
        ]);
    }
}
