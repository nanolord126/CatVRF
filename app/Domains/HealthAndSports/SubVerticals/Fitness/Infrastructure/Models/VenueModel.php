<?php

declare(strict_types=1);

namespace Modules\Fitness\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Fitness\Domain\Entities\Venue as VenueEntity;

final class VenueModel extends Model
{
    use SoftDeletes;

    protected $table = 'fitness_venues';

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'address',
        'city',
        'phone',
        'email',
        'latitude',
        'longitude',
        'capacity',
        'total_area',
        'amenities',
        'opening_hours',
        'is_active',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'capacity' => 'integer',
        'total_area' => 'integer',
        'amenities' => 'array',
        'is_active' => 'boolean',
    ];

    public function scheduleSlots(): HasMany
    {
        return $this->hasMany(ScheduleSlotModel::class, 'venue_id');
    }

    public function workoutSessions(): HasMany
    {
        return $this->hasMany(WorkoutSessionModel::class, 'venue_id');
    }

    public function toDomain(): VenueEntity
    {
        return new VenueEntity(
            id: $this->id,
            tenantId: $this->tenant_id,
            name: $this->name,
            description: $this->description,
            address: $this->address,
            city: $this->city,
            phone: $this->phone,
            email: $this->email,
            latitude: $this->latitude,
            longitude: $this->longitude,
            capacity: $this->capacity,
            totalArea: $this->total_area,
            amenities: $this->amenities,
            openingHours: $this->opening_hours,
            isActive: $this->is_active,
            createdAt: \Carbon\CarbonImmutable::parse($this->created_at),
            updatedAt: \Carbon\CarbonImmutable::parse($this->updated_at),
        );
    }

    public static function fromDomain(VenueEntity $entity): self
    {
        return new self([
            'id' => $entity->id > 0 ? $entity->id : null,
            'tenant_id' => $entity->tenantId,
            'name' => $entity->name,
            'description' => $entity->description,
            'address' => $entity->address,
            'city' => $entity->city,
            'phone' => $entity->phone,
            'email' => $entity->email,
            'latitude' => $entity->latitude,
            'longitude' => $entity->longitude,
            'capacity' => $entity->capacity,
            'total_area' => $entity->totalArea,
            'amenities' => $entity->amenities,
            'opening_hours' => $entity->openingHours,
            'is_active' => $entity->isActive,
        ]);
    }
}
