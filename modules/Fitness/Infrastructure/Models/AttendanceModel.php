<?php

declare(strict_types=1);

namespace Modules\Fitness\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Fitness\Domain\Entities\Attendance as AttendanceEntity;
use Modules\Fitness\Domain\Enums\AttendanceStatus;

final class AttendanceModel extends Model
{
    use SoftDeletes;

    protected $table = 'fitness_attendances';

    protected $fillable = [
        'tenant_id',
        'booking_id',
        'client_id',
        'schedule_slot_id',
        'status',
        'check_in_time',
        'check_out_time',
        'duration_minutes',
        'notes',
        'trainer_id',
        'performance_metrics',
    ];

    protected $casts = [
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
        'duration_minutes' => 'integer',
        'performance_metrics' => 'array',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(BookingModel::class, 'booking_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(ClientModel::class, 'client_id');
    }

    public function scheduleSlot(): BelongsTo
    {
        return $this->belongsTo(ScheduleSlotModel::class, 'schedule_slot_id');
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(TrainerModel::class, 'trainer_id');
    }

    public function toDomain(): AttendanceEntity
    {
        return new AttendanceEntity(
            id: $this->id,
            tenantId: $this->tenant_id,
            bookingId: $this->booking_id,
            clientId: $this->client_id,
            scheduleSlotId: $this->schedule_slot_id,
            status: AttendanceStatus::from($this->status),
            checkInTime: \Carbon\CarbonImmutable::parse($this->check_in_time),
            checkOutTime: $this->check_out_time ? \Carbon\CarbonImmutable::parse($this->check_out_time) : null,
            durationMinutes: $this->duration_minutes,
            notes: $this->notes,
            trainerId: $this->trainer_id,
            performanceMetrics: $this->performance_metrics,
            createdAt: \Carbon\CarbonImmutable::parse($this->created_at),
            updatedAt: \Carbon\CarbonImmutable::parse($this->updated_at),
        );
    }

    public static function fromDomain(AttendanceEntity $entity): self
    {
        return new self([
            'id' => $entity->id > 0 ? $entity->id : null,
            'tenant_id' => $entity->tenantId,
            'booking_id' => $entity->bookingId,
            'client_id' => $entity->clientId,
            'schedule_slot_id' => $entity->scheduleSlotId,
            'status' => $entity->status->value,
            'check_in_time' => $entity->checkInTime,
            'check_out_time' => $entity->checkOutTime,
            'duration_minutes' => $entity->durationMinutes,
            'notes' => $entity->notes,
            'trainer_id' => $entity->trainerId,
            'performance_metrics' => $entity->performanceMetrics,
        ]);
    }
}
