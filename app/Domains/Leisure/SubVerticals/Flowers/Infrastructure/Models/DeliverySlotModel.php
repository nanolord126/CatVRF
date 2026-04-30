<?php

declare(strict_types=1);

namespace Modules\Flowers\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Flowers\Domain\Entities\DeliverySlot as DeliverySlotEntity;

final class DeliverySlotModel extends Model
{
    protected $table = 'flowers_delivery_slots';

    protected $fillable = [
        'venue_id',
        'tenant_id',
        'date',
        'start_time',
        'end_time',
        'capacity',
        'booked_count',
        'is_available',
        'delivery_fee',
        'metadata',
    ];

    protected $casts = [
        'date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'capacity' => 'integer',
        'booked_count' => 'integer',
        'is_available' => 'boolean',
        'delivery_fee' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function venue(): BelongsTo
    {
        return $this->belongsTo(VenueModel::class, 'venue_id');
    }

    public function orders(): BelongsTo
    {
        return $this->hasMany(OrderModel::class, 'delivery_slot_id');
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_available', true)
            ->whereRaw('(capacity - booked_count) > 0');
    }

    public function scopeByVenue($query, int $venueId)
    {
        return $query->where('venue_id', $venueId);
    }

    public function scopeByDate($query, $date)
    {
        return $query->where('date', $date);
    }

    public function scopeFuture($query)
    {
        return $query->where('date', '>=', now()->toDateString());
    }

    public function toDomain(): DeliverySlotEntity
    {
        return new DeliverySlotEntity(
            id: $this->id,
            venueId: $this->venue_id,
            tenantId: $this->tenant_id,
            date: \Carbon\CarbonImmutable::parse($this->date),
            startTime: \Carbon\CarbonImmutable::parse($this->start_time),
            endTime: \Carbon\CarbonImmutable::parse($this->end_time),
            capacity: $this->capacity,
            bookedCount: $this->booked_count,
            isAvailable: $this->is_available,
            deliveryFee: (float) $this->delivery_fee,
            metadata: $this->metadata,
            createdAt: \Carbon\CarbonImmutable::parse($this->created_at),
            updatedAt: \Carbon\CarbonImmutable::parse($this->updated_at),
        );
    }

    public static function fromDomain(DeliverySlotEntity $entity): self
    {
        return new self([
            'id' => $entity->id > 0 ? $entity->id : null,
            'venue_id' => $entity->venueId,
            'tenant_id' => $entity->tenantId,
            'date' => $entity->date->format('Y-m-d'),
            'start_time' => $entity->startTime->format('H:i:s'),
            'end_time' => $entity->endTime->format('H:i:s'),
            'capacity' => $entity->capacity,
            'booked_count' => $entity->bookedCount,
            'is_available' => $entity->isAvailable,
            'delivery_fee' => $entity->deliveryFee,
            'metadata' => $entity->metadata,
        ]);
    }
}
