<?php

declare(strict_types=1);

namespace App\Domains\Staff\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

/**
 * StaffMentoring — отношение наставничества между сотрудниками.
 * CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class StaffMentoring extends Model
{
    use SoftDeletes;

    protected $table = 'staff_mentoring';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'mentor_id',
        'mentee_id',
        'goals',
        'focus_area',
        'start_date',
        'end_date',
        'status',
        'status_notes',
        'sessions_count',
        'sessions_completed',
        'effectiveness_rating',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'effectiveness_rating' => 'decimal:2',
        'sessions_count' => 'integer',
        'sessions_completed' => 'integer',
    ];

    protected $hidden = [
        'deleted_at',
    ];

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'mentor_id');
    }

    public function mentee(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'mentee_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(StaffMentoringSession::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeByMentor($query, int $mentorId)
    {
        return $query->where('mentor_id', $mentorId);
    }

    public function scopeByMentee($query, int $menteeId)
    {
        return $query->where('mentee_id', $menteeId);
    }

    // Accessors
    public function getProgressAttribute(): float
    {
        return $this->sessions_count > 0 
            ? ($this->sessions_completed / $this->sessions_count) * 100 
            : 0;
    }

    public function getIsCompletedAttribute(): bool
    {
        return $this->status === 'completed';
    }

    public function getDurationDaysAttribute(): ?int
    {
        if (!$this->end_date) {
            return null;
        }

        return $this->start_date->diffInDays($this->end_date);
    }
}
