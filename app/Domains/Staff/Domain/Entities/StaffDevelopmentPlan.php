<?php

declare(strict_types=1);

namespace App\Domains\Staff\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

/**
 * StaffDevelopmentPlan — план развития сотрудника (PDP).
 * CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class StaffDevelopmentPlan extends Model
{
    use SoftDeletes;

    protected $table = 'staff_development_plans';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'staff_id',
        'manager_id',
        'start_date',
        'end_date',
        'goals',
        'objectives',
        'development_activities',
        'status',
        'progress',
        'manager_notes',
        'employee_notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'goals' => 'json',
        'development_activities' => 'json',
        'progress' => 'decimal:2',
    ];

    protected $hidden = [
        'deleted_at',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'manager_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
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

    public function scopeOnHold($query)
    {
        return $query->where('status', 'on_hold');
    }

    // Accessors
    public function getIsCompletedAttribute(): bool
    {
        return $this->status === 'completed';
    }

    public function getProgressPercentageAttribute(): float
    {
        return (float) $this->progress;
    }

    public function getDurationDaysAttribute(): int
    {
        return $this->start_date->diffInDays($this->end_date);
    }

    public function getDaysRemainingAttribute(): int
    {
        return max(0, now()->diffInDays($this->end_date, false));
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->end_date->isPast() && !$this->is_completed;
    }
}
