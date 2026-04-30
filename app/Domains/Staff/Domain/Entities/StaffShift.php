<?php

declare(strict_types=1);

namespace App\Domains\Staff\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * StaffShift — смена сотрудника.
 * CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class StaffShift extends Model
{
    protected $table = 'staff_shifts';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'staff_id',
        'shift_date',
        'start_time',
        'end_time',
        'duration_minutes',
        'shift_type',
        'location',
        'status',
        'checked_in_at',
        'checked_out_at',
        'check_in_method',
        'check_in_lat',
        'check_in_lng',
        'check_out_lat',
        'check_out_lng',
        'notes',
    ];

    protected $casts = [
        'shift_date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'duration_minutes' => 'integer',
        'checked_in_at' => 'datetime',
        'checked_out_at' => 'datetime',
        'check_in_lat' => 'decimal:8',
        'check_in_lng' => 'decimal:8',
        'check_out_lat' => 'decimal:8',
        'check_out_lng' => 'decimal:8',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    // Scopes
    public function scopeToday($query)
    {
        return $query->where('shift_date', today());
    }

    public function scopeUpcoming($query)
    {
        return $query->where('shift_date', '>=', today())
                     ->where('status', 'scheduled');
    }

    public function scopePast($query)
    {
        return $query->where('shift_date', '<', today());
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('shift_type', $type);
    }

    public function scopeCheckedIn($query)
    {
        return $query->where('status', 'checked_in');
    }

    public function scopeCheckedOut($query)
    {
        return $query->where('status', 'checked_out');
    }

    public function scopeMissed($query)
    {
        return $query->where('status', 'missed');
    }

    // Accessors
    public function getIsTodayAttribute(): bool
    {
        return $this->shift_date->isToday();
    }

    public function getIsFutureAttribute(): bool
    {
        return $this->shift_date->isFuture();
    }

    public function getIsPastAttribute(): bool
    {
        return $this->shift_date->isPast();
    }

    public function getIsCheckedInAttribute(): bool
    {
        return $this->status === 'checked_in';
    }

    public function getIsCheckedOutAttribute(): bool
    {
        return $this->status === 'checked_out';
    }

    public function getIsMissedAttribute(): bool
    {
        return $this->status === 'missed';
    }

    public function getActualDurationAttribute(): ?int
    {
        if (!$this->checked_in_at || !$this->checked_out_at) {
            return null;
        }

        return $this->checked_in_at->diffInMinutes($this->checked_out_at);
    }

    public function getDurationHoursAttribute(): float
    {
        return $this->duration_minutes / 60;
    }
}
