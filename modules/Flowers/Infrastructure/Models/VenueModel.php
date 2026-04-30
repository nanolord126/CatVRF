<?php

declare(strict_types=1);

namespace Modules\Flowers\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Flowers\Domain\Entities\Venue as VenueEntity;
use Modules\Media\Domain\Traits\HasMediaTrait;

final class VenueModel extends Model
{
    use SoftDeletes;
    use HasMediaTrait;

    protected $table = 'flowers_venues';

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'description',
        'address',
        'city',
        'phone',
        'email',
        'working_hours',
        'latitude',
        'longitude',
        'is_active',
        'supports_delivery',
        'supports_pickup',
        'delivery_radius_km',
        'preparation_time_minutes',
        'settings',
    ];

    protected $casts = [
        'working_hours' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_active' => 'boolean',
        'supports_delivery' => 'boolean',
        'supports_pickup' => 'boolean',
        'delivery_radius_km' => 'integer',
        'preparation_time_minutes' => 'integer',
        'settings' => 'array',
    ];

    public function flowers(): HasMany
    {
        return $this->hasMany(FlowerModel::class, 'venue_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(ProductModel::class, 'venue_id');
    }

    public function modifiers(): HasMany
    {
        return $this->hasMany(ModifierModel::class, 'venue_id');
    }

    public function florists(): HasMany
    {
        return $this->hasMany(FloristModel::class, 'venue_id');
    }

    public function deliverySlots(): HasMany
    {
        return $this->hasMany(DeliverySlotModel::class, 'venue_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(OrderModel::class, 'venue_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCity($query, string $city)
    {
        return $query->where('city', $city);
    }

    public function scopeSupportsDelivery($query)
    {
        return $query->where('supports_delivery', true);
    }

    public function toDomain(): VenueEntity
    {
        return new VenueEntity(
            id: $this->id,
            tenantId: $this->tenant_id,
            name: $this->name,
            slug: $this->slug,
            description: $this->description,
            address: $this->address,
            city: $this->city,
            phone: $this->phone,
            email: $this->email,
            workingHours: $this->working_hours,
            latitude: $this->latitude ? (float) $this->latitude : null,
            longitude: $this->longitude ? (float) $this->longitude : null,
            isActive: $this->is_active,
            supportsDelivery: $this->supports_delivery,
            supportsPickup: $this->supports_pickup,
            deliveryRadiusKm: $this->delivery_radius_km,
            preparationTimeMinutes: $this->preparation_time_minutes,
            settings: $this->settings,
            createdAt: \Carbon\CarbonImmutable::parse($this->created_at),
            updatedAt: \Carbon\CarbonImmutable::parse($this->updated_at),
            deletedAt: $this->deleted_at ? \Carbon\CarbonImmutable::parse($this->deleted_at) : null,
        );
    }

    public static function fromDomain(VenueEntity $entity): self
    {
        return new self([
            'id' => $entity->id > 0 ? $entity->id : null,
            'tenant_id' => $entity->tenantId,
            'name' => $entity->name,
            'slug' => $entity->slug,
            'description' => $entity->description,
            'address' => $entity->address,
            'city' => $entity->city,
            'phone' => $entity->phone,
            'email' => $entity->email,
            'working_hours' => $entity->workingHours,
            'latitude' => $entity->latitude,
            'longitude' => $entity->longitude,
            'is_active' => $entity->isActive,
            'supports_delivery' => $entity->supportsDelivery,
            'supports_pickup' => $entity->supportsPickup,
            'delivery_radius_km' => $entity->deliveryRadiusKm,
            'preparation_time_minutes' => $entity->preparationTimeMinutes,
            'settings' => $entity->settings,
        ]);
    }
}
