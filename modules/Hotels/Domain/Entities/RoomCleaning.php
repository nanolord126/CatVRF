<?php

declare(strict_types=1);

namespace Modules\Hotels\Domain\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class RoomCleaning extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'hotel_id',
        'room_id',
        'assigned_staff_id',
        'assigned_team_id',
        'cleaning_type',
        'priority',
        'status',
        'scheduled_at',
        'started_at',
        'completed_at',
        'duration_minutes',
        'checklist_completed',
        'checklist_items',
        'notes',
        'photos',
        'supervisor_id',
        'supervisor_approved',
        'supervisor_notes',
        'quality_score',
        'metadata',
        'correlation_id',
        'uuid',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'duration_minutes' => 'integer',
        'checklist_completed' => 'boolean',
        'checklist_items' => 'json',
        'photos' => 'json',
        'supervisor_approved' => 'boolean',
        'quality_score' => 'integer',
        'priority' => 'integer',
        'metadata' => 'json',
    ];

    protected $table = 'hotel_room_cleanings';

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(\Modules\Hotels\Domain\Entities\Hotel::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(\Modules\Hotels\Domain\Entities\Room::class);
    }

    public function assignedStaff(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_staff_id');
    }

    public function assignedTeam(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Team::class, 'assigned_team_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'supervisor_id');
    }

    public function scopeByHotel($query, int $hotelId)
    {
        return $query->where('hotel_id', $hotelId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending')
            ->where('scheduled_at', '<=', now()->addHours(2));
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'pending')
            ->where('scheduled_at', '<', now());
    }

    public function start(): bool
    {
        return $this->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);
    }

    public function complete(array $checklistItems, ?string $notes = null, ?array $photos = null): bool
    {
        return $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'duration_minutes' => $this->started_at ? now()->diffInMinutes($this->started_at) : null,
            'checklist_items' => $checklistItems,
            'checklist_completed' => collect($checklistItems)->every(fn($i) => $i['completed']),
            'notes' => $notes,
            'photos' => $photos,
        ]);
    }

    public function approve(int $score, ?string $notes = null): bool
    {
        return $this->update([
            'supervisor_approved' => true,
            'supervisor_notes' => $notes,
            'quality_score' => $score,
        ]);
    }

    public function reject(string $reason): bool
    {
        return $this->update([
            'supervisor_approved' => false,
            'supervisor_notes' => $reason,
            'status' => 'needs_rework',
        ]);
    }

    protected static function booted(): void
    {
        parent::booted();
        static::creating(fn ($m) => $m->uuid ??= \Str::uuid());
    }
}
