<?php

declare(strict_types=1);

namespace App\Domains\Staff\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * StaffMentoringSession — сессия наставничества.
 * CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class StaffMentoringSession extends Model
{
    protected $table = 'staff_mentoring_sessions';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'mentoring_id',
        'scheduled_at',
        'completed_at',
        'duration_minutes',
        'topics',
        'notes',
        'action_items',
        'status',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
        'duration_minutes' => 'integer',
    ];

    public function mentoring(): BelongsTo
    {
        return $this->belongsTo(StaffMentoring::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    // Scopes
    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('status', 'scheduled')
                     ->where('scheduled_at', '>', now());
    }

    public function scopePast($query)
    {
        return $query->where('scheduled_at', '<', now());
    }

    // Accessors
    public function getIsCompletedAttribute(): bool
    {
        return $this->status === 'completed';
    }

    public function getIsMissedAttribute(): bool
    {
        return $this->status === 'no_show';
    }

    public function getActualDurationAttribute(): ?int
    {
        if (!$this->completed_at) {
            return null;
        }

        return $this->scheduled_at->diffInMinutes($this->completed_at);
    }
}
