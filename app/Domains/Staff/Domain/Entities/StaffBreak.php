<?php

declare(strict_types=1);

namespace App\Domains\Staff\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * StaffBreak — перерыв сотрудника.
 * CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class StaffBreak extends Model
{
    protected $table = 'staff_breaks';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'staff_id',
        'shift_id',
        'break_type',
        'start_time',
        'end_time',
        'duration_minutes',
        'location',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'duration_minutes' => 'integer',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(StaffShift::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    // Scopes
    public function scopeLunch($query)
    {
        return $query->where('break_type', 'lunch');
    }

    public function scopeRest($query)
    {
        return $query->where('break_type', 'rest');
    }

    public function scopeActive($query)
    {
        return $query->whereNull('end_time');
    }

    public function scopeCompleted($query)
    {
        return $query->whereNotNull('end_time');
    }

    // Accessors
    public function getIsActiveAttribute(): bool
    {
        return $this->end_time === null;
    }

    public function getIsCompletedAttribute(): bool
    {
        return $this->end_time !== null;
    }

    public function getDurationHoursAttribute(): float
    {
        return $this->duration_minutes / 60;
    }

    public function getActualDurationAttribute(): ?int
    {
        if (!$this->start_time || !$this->end_time) {
            return null;
        }

        return $this->start_time->diffInMinutes($this->end_time);
    }
}
