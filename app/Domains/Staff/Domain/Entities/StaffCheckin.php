<?php

declare(strict_types=1);

namespace App\Domains\Staff\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * StaffCheckin — чек-ин сотрудника для благополучия.
 * CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class StaffCheckin extends Model
{
    protected $table = 'staff_checkins';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'staff_id',
        'checkin_type',
        'mood',
        'energy_level',
        'stress_level',
        'work_life_balance',
        'notes',
    ];

    protected $casts = [
        'mood' => 'integer',
        'energy_level' => 'integer',
        'stress_level' => 'integer',
        'work_life_balance' => 'integer',
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
    public function scopeDaily($query)
    {
        return $query->where('checkin_type', 'daily');
    }

    public function scopeWeekly($query)
    {
        return $query->where('checkin_type', 'weekly');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeByPeriod($query, string $start, string $end)
    {
        return $query->whereBetween('created_at', [$start, $end]);
    }

    // Accessors
    public function getMoodLabelAttribute(): string
    {
        return match(true) {
            $this->mood >= 8 => 'Excellent',
            $this->mood >= 6 => 'Good',
            $this->mood >= 4 => 'Neutral',
            $this->mood >= 2 => 'Poor',
            default => 'Critical',
        };
    }

    public function getStressLabelAttribute(): string
    {
        return match(true) {
            $this->stress_level <= 2 => 'Low',
            $this->stress_level <= 4 => 'Moderate',
            $this->stress_level <= 6 => 'High',
            default => 'Critical',
        };
    }

    public function getNeedsAttentionAttribute(): bool
    {
        return $this->mood <= 3 || $this->stress_level >= 7 || $this->work_life_balance <= 3;
    }
}
