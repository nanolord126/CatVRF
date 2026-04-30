<?php

declare(strict_types=1);

namespace Modules\Hotels\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShiftModel extends Model
{
    use SoftDeletes;

    protected $table = 'hotels_shifts';

    protected $fillable = [
        'tenant_id',
        'venue_id',
        'user_id',
        'uuid',
        'role',
        'start_time',
        'end_time',
        'status',
        'location',
        'assigned_tasks',
        'breaks',
        'notes',
        'supervisor_id',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'assigned_tasks' => 'array',
        'breaks' => 'array',
    ];

    public function venue(): BelongsTo
    {
        return $this->belongsTo(VenueModel::class, 'venue_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'supervisor_id');
    }

    public function toDomain(): \Modules\Hotels\Domain\Entities\Shift
    {
        return new \Modules\Hotels\Domain\Entities\Shift(
            id: $this->id,
            tenantId: $this->tenant_id,
            venueId: $this->venue_id,
            userId: $this->user_id,
            uuid: $this->uuid,
            role: $this->role,
            startTime: \Carbon\CarbonImmutable::parse($this->start_time),
            endTime: \Carbon\CarbonImmutable::parse($this->end_time),
            status: $this->status,
            location: $this->location,
            assignedTasks: $this->assigned_tasks,
            breaks: $this->breaks,
            notes: $this->notes,
            supervisorId: $this->supervisor_id,
            createdAt: \Carbon\CarbonImmutable::parse($this->created_at),
            updatedAt: \Carbon\CarbonImmutable::parse($this->updated_at),
        );
    }

    public static function fromDomain(\Modules\Hotels\Domain\Entities\Shift $shift): self
    {
        return new self([
            'id' => $shift->id,
            'tenant_id' => $shift->tenantId,
            'venue_id' => $shift->venueId,
            'user_id' => $shift->userId,
            'uuid' => $shift->uuid,
            'role' => $shift->role,
            'start_time' => $shift->startTime->toDateTimeString(),
            'end_time' => $shift->endTime->toDateTimeString(),
            'status' => $shift->status,
            'location' => $shift->location,
            'assigned_tasks' => $shift->assignedTasks,
            'breaks' => $shift->breaks,
            'notes' => $shift->notes,
            'supervisor_id' => $shift->supervisorId,
        ]);
    }
}
