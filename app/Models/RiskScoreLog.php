<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Risk Score Log Model
 *
 * Logs adaptive authentication risk assessments.
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise Security
 */
final class RiskScoreLog extends Model
{
    use HasFactory;

    protected $table = 'risk_score_logs';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'risk_score',
        'risk_level',
        'step_up_required',
        'behavioral_score',
        'device_risk',
        'geo_velocity_risk',
        'time_pattern_risk',
        'auth_history_risk',
        'ml_risk',
        'context',
        'correlation_id',
        'was_blocked',
        'block_reason',
    ];

    protected $casts = [
        'risk_score' => 'float',
        'step_up_required' => 'array',
        'behavioral_score' => 'float',
        'device_risk' => 'float',
        'geo_velocity_risk' => 'float',
        'time_pattern_risk' => 'float',
        'auth_history_risk' => 'float',
        'ml_risk' => 'float',
        'context' => 'array',
        'was_blocked' => 'boolean',
    ];

    /**
     * Relationship to User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for high-risk logs
     */
    public function scopeHighRisk($query)
    {
        return $query->where('risk_level', '>=', 'high');
    }

    /**
     * Scope for critical logs
     */
    public function scopeCritical($query)
    {
        return $query->where('risk_level', 'critical');
    }

    /**
     * Scope for blocked logs
     */
    public function scopeBlocked($query)
    {
        return $query->where('was_blocked', true);
    }

    /**
     * Scope for recent logs
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', CarbonImmutable::now()->subDays($days));
    }

    /**
     * Check if log indicates high risk
     */
    public function isHighRisk(): bool
    {
        return in_array($this->risk_level, ['high', 'critical'], true);
    }

    /**
     * Check if step-up was required
     */
    public function requiredStepUp(): bool
    {
        return ! empty($this->step_up_required);
    }
}
