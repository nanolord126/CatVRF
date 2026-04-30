<?php

declare(strict_types=1);

namespace App\Domains\Staff\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

/**
 * StaffPrediction — AI-предсказание для сотрудника.
 * CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Типы предсказаний: burnout_risk, turnover_risk, performance_trend, skill_gap
 */
final class StaffPrediction extends Model
{
    use SoftDeletes;

    protected $table = 'staff_predictions';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'staff_id',
        'prediction_type',
        'confidence_score',
        'risk_level',
        'prediction_data',
        'factors',
        'recommendations',
        'prediction_date',
        'target_date',
        'is_confirmed',
    ];

    protected $casts = [
        'confidence_score' => 'decimal:2',
        'prediction_data' => 'json',
        'factors' => 'json',
        'prediction_date' => 'datetime',
        'target_date' => 'datetime',
        'is_confirmed' => 'boolean',
    ];

    protected $hidden = [
        'deleted_at',
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
    public function scopeByType($query, string $type)
    {
        return $query->where('prediction_type', $type);
    }

    public function scopeHighRisk($query)
    {
        return $query->whereIn('risk_level', ['high', 'critical']);
    }

    public function scopeConfirmed($query)
    {
        return $query->where('is_confirmed', true);
    }

    public function scopePending($query)
    {
        return $query->whereNull('is_confirmed');
    }

    // Accessors
    public function getIsHighRiskAttribute(): bool
    {
        return in_array($this->risk_level, ['high', 'critical']);
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->target_date && $this->target_date->isPast();
    }
}
