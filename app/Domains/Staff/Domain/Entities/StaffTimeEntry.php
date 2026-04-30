<?php

declare(strict_types=1);

namespace App\Domains\Staff\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * StaffTimeEntry — запись рабочего времени сотрудника.
 * CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class StaffTimeEntry extends Model
{
    protected $table = 'staff_time_entries';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'staff_id',
        'shift_id',
        'start_time',
        'end_time',
        'duration_minutes',
        'entry_type',
        'project_code',
        'description',
        'lat',
        'lng',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'duration_minutes' => 'integer',
        'lat' => 'decimal:8',
        'lng' => 'decimal:8',
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
    public function scopeByType($query, string $type)
    {
        return $query->where('entry_type', $type);
    }

    public function scopeWork($query)
    {
        return $query->where('entry_type', 'work');
    }

    public function scopeBreak($query)
    {
        return $query->where('entry_type', 'break');
    }

    public function scopeOvertime($query)
    {
        return $query->where('entry_type', 'overtime');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('start_time', today());
    }

    public function scopeByDate($query, Carbon $date)
    {
        return $query->whereDate('start_time', $date);
    }

    // Accessors
    public function getDurationHoursAttribute(): float
    {
        return $this->duration_minutes / 60;
    }

    public function getIsWorkAttribute(): bool
    {
        return $this->entry_type === 'work';
    }

    public function getIsBreakAttribute(): bool
    {
        return $this->entry_type === 'break';
    }

    public function getIsOvertimeAttribute(): bool
    {
        return $this->entry_type === 'overtime';
    }

    public function getIsRunningAttribute(): bool
    {
        return $this->end_time === null;
    }
}
