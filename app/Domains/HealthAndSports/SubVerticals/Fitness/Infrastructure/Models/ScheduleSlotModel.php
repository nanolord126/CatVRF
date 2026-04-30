<?php

declare(strict_types=1);

namespace Modules\Fitness\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Fitness\Domain\Entities\ScheduleSlot as ScheduleSlotEntity;

final class ScheduleSlotModel extends Model
{
    use SoftDeletes;

    protected $table = 'fitness_schedule_slots';

    protected $fillable = [
        'tenant_id',
        'venue_id',
        'trainer_id',
        'workout_type_id',
        'start_time',
        'end_time',
        'capacity',
        'booked_count',
        'is_recurring',
        'recurrence_pattern',
        'recurrence_end',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'recurrence_end' => 'datetime',
        'capacity' => 'integer',
        'booked_count' => 'integer',
        'is_recurring' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function venue(): BelongsTo
    {
        return $this->belongsTo(VenueModel::class, 'venue_id');
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(TrainerModel::class, 'trainer_id');
    }

    public function workoutType(): BelongsTo
    {
        return $this->belongsTo(WorkoutTypeModel::class, 'workout_type_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(BookingModel::class, 'schedule_slot_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(AttendanceModel::class, 'schedule_slot_id');
    }

    public function workoutSession(): HasMany
    {
        return $this->hasMany(WorkoutSessionModel::class, 'schedule_slot_id');
    }

    public function toDomain(): ScheduleSlotEntity
    {
        return new ScheduleSlotEntity(
            id: $this->id,
            tenantId: $this->tenant_id,
            venueId: $this->venue_id,
            trainerId: $this->trainer_id,
            workoutTypeId: $this->workout_type_id,
            startTime: \Carbon\CarbonImmutable::parse($this->start_time),
            endTime: \Carbon\CarbonImmutable::parse($this->end_time),
            capacity: $this->capacity,
            bookedCount: $this->booked_count,
            isRecurring: $this->is_recurring,
            recurrencePattern: $this->recurrence_pattern,
            recurrenceEnd: $this->recurrence_end ? \Carbon\CarbonImmutable::parse($this->recurrence_end) : null,
            notes: $this->notes,
            isActive: $this->is_active,
            createdAt: \Carbon\CarbonImmutable::parse($this->created_at),
            updatedAt: \Carbon\CarbonImmutable::parse($this->updated_at),
        );
    }

    public static function fromDomain(ScheduleSlotEntity $entity): self
    {
        return new self([
            'id' => $entity->id > 0 ? $entity->id : null,
            'tenant_id' => $entity->tenantId,
            'venue_id' => $entity->venueId,
            'trainer_id' => $entity->trainerId,
            'workout_type_id' => $entity->workoutTypeId,
            'start_time' => $entity->startTime,
            'end_time' => $entity->endTime,
            'capacity' => $entity->capacity,
            'booked_count' => $entity->bookedCount,
            'is_recurring' => $entity->isRecurring,
            'recurrence_pattern' => $entity->recurrencePattern,
            'recurrence_end' => $entity->recurrenceEnd,
            'notes' => $entity->notes,
            'is_active' => $entity->isActive,
        ]);
    }
}
