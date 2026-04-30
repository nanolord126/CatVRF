<?php

declare(strict_types=1);

namespace App\Domains\Staff\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * StaffCourseEnrollment — запись сотрудника на курс.
 * CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class StaffCourseEnrollment extends Model
{
    protected $table = 'staff_course_enrollments';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'staff_id',
        'course_id',
        'progress',
        'modules_completed',
        'total_modules',
        'time_spent_minutes',
        'started_at',
        'completed_at',
        'due_date',
        'status',
        'score',
        'feedback',
    ];

    protected $casts = [
        'progress' => 'decimal:2',
        'modules_completed' => 'integer',
        'total_modules' => 'integer',
        'time_spent_minutes' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'due_date' => 'datetime',
        'score' => 'decimal:2',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(StaffCourse::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    // Scopes
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
        return $query->where('due_date', '<', now())
                     ->where('status', '!=', 'completed');
    }

    // Accessors
    public function getIsCompletedAttribute(): bool
    {
        return $this->status === 'completed';
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date && $this->due_date->isPast() && !$this->is_completed;
    }

    public function getProgressPercentageAttribute(): float
    {
        return (float) $this->progress;
    }

    public function getTimeSpentHoursAttribute(): float
    {
        return $this->time_spent_minutes / 60;
    }
}
