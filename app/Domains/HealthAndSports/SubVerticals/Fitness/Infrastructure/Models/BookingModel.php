<?php

declare(strict_types=1);

namespace Modules\Fitness\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Fitness\Domain\Entities\Booking as BookingEntity;
use Modules\Fitness\Domain\Enums\BookingStatus;

final class BookingModel extends Model
{
    use SoftDeletes;

    protected $table = 'fitness_bookings';

    protected $fillable = [
        'tenant_id',
        'client_id',
        'schedule_slot_id',
        'status',
        'membership_id',
        'booked_at',
        'checked_in_at',
        'cancelled_at',
        'cancellation_reason',
        'notes',
        'is_paid',
        'price',
    ];

    protected $casts = [
        'booked_at' => 'datetime',
        'checked_in_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'is_paid' => 'boolean',
        'price' => 'float',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(ClientModel::class, 'client_id');
    }

    public function scheduleSlot(): BelongsTo
    {
        return $this->belongsTo(ScheduleSlotModel::class, 'schedule_slot_id');
    }

    public function membership(): BelongsTo
    {
        return $this->belongsTo(MembershipModel::class, 'membership_id');
    }

    public function attendance(): HasOne
    {
        return $this->hasOne(AttendanceModel::class, 'booking_id');
    }

    public function toDomain(): BookingEntity
    {
        return new BookingEntity(
            id: $this->id,
            tenantId: $this->tenant_id,
            clientId: $this->client_id,
            scheduleSlotId: $this->schedule_slot_id,
            status: BookingStatus::from($this->status),
            membershipId: $this->membership_id,
            bookedAt: \Carbon\CarbonImmutable::parse($this->booked_at),
            checkedInAt: $this->checked_in_at ? \Carbon\CarbonImmutable::parse($this->checked_in_at) : null,
            cancelledAt: $this->cancelled_at ? \Carbon\CarbonImmutable::parse($this->cancelled_at) : null,
            cancellationReason: $this->cancellation_reason,
            notes: $this->notes,
            isPaid: $this->is_paid,
            price: $this->price,
            createdAt: \Carbon\CarbonImmutable::parse($this->created_at),
            updatedAt: \Carbon\CarbonImmutable::parse($this->updated_at),
        );
    }

    public static function fromDomain(BookingEntity $entity): self
    {
        return new self([
            'id' => $entity->id > 0 ? $entity->id : null,
            'tenant_id' => $entity->tenantId,
            'client_id' => $entity->clientId,
            'schedule_slot_id' => $entity->scheduleSlotId,
            'status' => $entity->status->value,
            'membership_id' => $entity->membershipId,
            'booked_at' => $entity->bookedAt,
            'checked_in_at' => $entity->checkedInAt,
            'cancelled_at' => $entity->cancelledAt,
            'cancellation_reason' => $entity->cancellationReason,
            'notes' => $entity->notes,
            'is_paid' => $entity->isPaid,
            'price' => $entity->price,
        ]);
    }
}
