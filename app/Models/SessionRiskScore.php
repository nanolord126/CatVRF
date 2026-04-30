<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SessionRiskScore extends Model
{
    protected $fillable = [
        'session_id',
        'user_id',
        'tenant_id',
        'overall_risk_score',
        'risk_level',
        'factor_scores',
        'trust_score',
        'trust_reset_at',
        'behavioral_anomalies',
        'geographic_anomalies',
        'device_anomalies',
        'challenge_required',
        'challenge_type',
        'challenge_triggered_at',
        'challenge_completed_at',
        'challenge_passed',
        'terminated',
        'termination_reason',
        'terminated_at',
        'last_scored_at',
        'monitoring_started_at',
        'monitoring_ended_at',
    ];

    protected $casts = [
        'overall_risk_score' => 'integer',
        'factor_scores' => 'json',
        'trust_score' => 'float',
        'behavioral_anomalies' => 'json',
        'geographic_anomalies' => 'json',
        'device_anomalies' => 'json',
        'challenge_required' => 'boolean',
        'challenge_triggered_at' => 'datetime',
        'challenge_completed_at' => 'datetime',
        'challenge_passed' => 'boolean',
        'terminated' => 'boolean',
        'terminated_at' => 'datetime',
        'trust_reset_at' => 'datetime',
        'last_scored_at' => 'datetime',
        'monitoring_started_at' => 'datetime',
        'monitoring_ended_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isLowRisk(): bool
    {
        return $this->risk_level === 'low' && $this->overall_risk_score < 30;
    }

    public function isMediumRisk(): bool
    {
        return $this->risk_level === 'medium' && $this->overall_risk_score >= 30 && $this->overall_risk_score < 60;
    }

    public function isHighRisk(): bool
    {
        return $this->risk_level === 'high' && $this->overall_risk_score >= 60 && $this->overall_risk_score < 80;
    }

    public function isCriticalRisk(): bool
    {
        return $this->risk_level === 'critical' && $this->overall_risk_score >= 80;
    }

    public function requiresChallenge(): bool
    {
        return $this->challenge_required;
    }

    public function isTerminated(): bool
    {
        return $this->terminated;
    }

    public function isMonitoring(): bool
    {
        return $this->monitoring_started_at !== null && $this->monitoring_ended_at === null;
    }

    public function scopeActive($query)
    {
        return $query->where('terminated', false)
            ->where('monitoring_started_at', '!=', null)
            ->where('monitoring_ended_at', null);
    }

    public function scopePendingChallenge($query)
    {
        return $query->where('challenge_required', true)
            ->where('challenge_passed', null);
    }

    public function scopeByRiskLevel($query, string $level)
    {
        return $query->where('risk_level', $level);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
