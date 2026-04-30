<?php

declare(strict_types=1);

namespace App\Domains\Staff\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * StaffBreakReminder — настройки напоминаний о перерывах.
 * CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class StaffBreakReminder extends Model
{
    protected $table = 'staff_break_reminders';

    protected $fillable = [
        'tenant_id',
        'staff_id',
        'reminder_interval_minutes',
        'work_hours_start',
        'work_hours_end',
        'is_enabled',
    ];

    protected $casts = [
        'reminder_interval_minutes' => 'integer',
        'is_enabled' => 'boolean',
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
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    public function scopeDisabled($query)
    {
        return $query->where('is_enabled', false);
    }

    // Accessors
    public function getIsEnabledAttribute(): bool
    {
        return $this->is_enabled;
    }

    public function getReminderIntervalHoursAttribute(): float
    {
        return $this->reminder_interval_minutes / 60;
    }
}
