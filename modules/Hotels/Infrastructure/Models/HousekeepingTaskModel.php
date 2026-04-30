<?php

declare(strict_types=1);

namespace Modules\Hotels\Infrastructure\Models;

use App\Models\BaseDomainModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class HousekeepingTaskModel extends BaseDomainModel
{
    use HasFactory;

    protected $table = 'hotels_housekeeping_tasks';

    protected $fillable = [
        'tenant_id',
        'venue_id',
        'room_id',
        'uuid',
        'task_type',
        'priority',
        'status',
        'scheduled_for',
        'started_at',
        'completed_at',
        'assigned_to',
        'completed_by',
        'checklist',
        'notes',
        'photos_before',
        'photos_after',
        'estimated_minutes',
        'actual_minutes',
        'booking_item_id',
    ];

    protected $casts = [
        'task_type' => 'string',
        'priority' => 'string',
        'status' => 'string',
        'scheduled_for' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'checklist' => 'array',
        'notes' => 'array',
        'photos_before' => 'array',
        'photos_after' => 'array',
        'estimated_minutes' => 'integer',
        'actual_minutes' => 'integer',
    ];

    public function venue(): BelongsTo
    {
        return $this->belongsTo(VenueModel::class, 'venue_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(RoomModel::class, 'room_id');
    }

    public function bookingItem(): BelongsTo
    {
        return $this->belongsTo(BookingItemModel::class, 'booking_item_id');
    }

    public function assignedToUser(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_to');
    }
}
