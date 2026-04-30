<?php

declare(strict_types=1);

namespace App\Domains\Staff\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * StaffFitnessData — данные фитнес-трекера.
 * CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class StaffFitnessData extends Model
{
    protected $table = 'staff_fitness_data';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'staff_id',
        'recorded_at',
        'steps',
        'calories_burned',
        'active_minutes',
        'distance_km',
        'heart_rate_avg',
        'heart_rate_max',
        'sleep_hours',
        'sleep_quality_score',
        'source',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'steps' => 'integer',
        'calories_burned' => 'integer',
        'active_minutes' => 'integer',
        'distance_km' => 'decimal:2',
        'heart_rate_avg' => 'integer',
        'heart_rate_max' => 'integer',
        'sleep_hours' => 'decimal:2',
        'sleep_quality_score' => 'integer',
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
    public function scopeByDate($query, string $date)
    {
        return $query->whereDate('recorded_at', $date);
    }

    public function scopeBySource($query, string $source)
    {
        return $query->where('source', $source);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('recorded_at', today());
    }

    // Accessors
    public function getStepsGoalAttribute(): int
    {
        return 10000;
    }

    public function getStepsPercentageAttribute(): float
    {
        return min(100, ($this->steps / $this->steps_goal) * 100);
    }

    public function getHasSleepDataAttribute(): bool
    {
        return $this->sleep_hours !== null;
    }

    public function getSleepQualityLabelAttribute(): string
    {
        if ($this->sleep_quality_score === null) {
            return 'N/A';
        }

        return match(true) {
            $this->sleep_quality_score >= 80 => 'Excellent',
            $this->sleep_quality_score >= 60 => 'Good',
            $this->sleep_quality_score >= 40 => 'Fair',
            $this->sleep_quality_score >= 20 => 'Poor',
            default => 'Very Poor',
        };
    }
}
