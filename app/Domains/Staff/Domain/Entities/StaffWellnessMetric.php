<?php

declare(strict_types=1);

namespace App\Domains\Staff\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * StaffWellnessMetric — агрегированные метрики благополучия.
 * CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class StaffWellnessMetric extends Model
{
    protected $table = 'staff_wellness_metrics';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'staff_id',
        'period_start',
        'period_end',
        'average_mood',
        'average_stress',
        'average_energy',
        'average_work_life_balance',
        'breaks_taken',
        'total_break_minutes',
        'burnout_risk_score',
        'overall_wellness_score',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'average_mood' => 'decimal:2',
        'average_stress' => 'decimal:2',
        'average_energy' => 'decimal:2',
        'average_work_life_balance' => 'decimal:2',
        'breaks_taken' => 'integer',
        'total_break_minutes' => 'integer',
        'burnout_risk_score' => 'decimal:2',
        'overall_wellness_score' => 'decimal:2',
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
    public function scopeByPeriod($query, Carbon $start, Carbon $end)
    {
        return $query->where('period_start', '>=', $start)
                     ->where('period_end', '<=', $end);
    }

    public function scopeHighBurnoutRisk($query)
    {
        return $query->where('burnout_risk_score', '>=', 70);
    }

    public function scopeLowWellness($query)
    {
        return $query->where('overall_wellness_score', '<=', 40);
    }

    // Accessors
    public function getBurnoutRiskLabelAttribute(): string
    {
        return match(true) {
            $this->burnout_risk_score >= 80 => 'Critical',
            $this->burnout_risk_score >= 60 => 'High',
            $this->burnout_risk_score >= 40 => 'Moderate',
            $this->burnout_risk_score >= 20 => 'Low',
            default => 'Minimal',
        };
    }

    public function getWellnessLabelAttribute(): string
    {
        return match(true) {
            $this->overall_wellness_score >= 80 => 'Excellent',
            $this->overall_wellness_score >= 60 => 'Good',
            $this->overall_wellness_score >= 40 => 'Fair',
            $this->overall_wellness_score >= 20 => 'Poor',
            default => 'Critical',
        };
    }

    public function getAvgBreakHoursAttribute(): float
    {
        return $this->total_break_minutes / 60;
    }

    public function getNeedsInterventionAttribute(): bool
    {
        return $this->burnout_risk_score >= 70 || $this->overall_wellness_score <= 40;
    }
}
