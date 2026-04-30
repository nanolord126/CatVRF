<?php

declare(strict_types=1);

namespace App\Domains\Staff\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * StaffAiAnalytics — AI-анализ производительности сотрудника.
 * CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Содержит AI-оценки, выявленные паттерны, рекомендации.
 */
final class StaffAiAnalytics extends Model
{
    protected $table = 'staff_ai_analytics';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'staff_id',
        'period_start',
        'period_end',
        'ai_performance_score',
        'ai_efficiency_score',
        'ai_quality_score',
        'ai_collaboration_score',
        'patterns',
        'strengths',
        'weaknesses',
        'recommendations',
        'team_percentile',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'ai_performance_score' => 'decimal:2',
        'ai_efficiency_score' => 'decimal:2',
        'ai_quality_score' => 'decimal:2',
        'ai_collaboration_score' => 'decimal:2',
        'patterns' => 'json',
        'strengths' => 'json',
        'weaknesses' => 'json',
        'recommendations' => 'json',
        'team_percentile' => 'decimal:2',
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

    public function scopeTopPerformers($query, float $percentile = 90.0)
    {
        return $query->where('team_percentile', '>=', $percentile);
    }

    public function scopeLowPerformers($query, float $percentile = 25.0)
    {
        return $query->where('team_percentile', '<=', $percentile);
    }

    // Accessors
    public function getOverallScoreAttribute(): float
    {
        return ($this->ai_performance_score + 
                $this->ai_efficiency_score + 
                $this->ai_quality_score + 
                $this->ai_collaboration_score) / 4;
    }

    public function getIsTopPerformerAttribute(): bool
    {
        return $this->team_percentile >= 90.0;
    }

    public function getIsLowPerformerAttribute(): bool
    {
        return $this->team_percentile <= 25.0;
    }

    // Methods
    public function getPatternSummary(): array
    {
        return $this->patterns ?? [];
    }

    public function getTopStrengths(int $limit = 3): array
    {
        return array_slice($this->strengths ?? [], 0, $limit);
    }

    public function getTopWeaknesses(int $limit = 3): array
    {
        return array_slice($this->weaknesses ?? [], 0, $limit);
    }

    public function getPriorityRecommendations(int $limit = 5): array
    {
        return array_slice($this->recommendations ?? [], 0, $limit);
    }
}
