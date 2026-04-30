<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class BusinessRiskScore extends Model
{
    protected $fillable = [
        'tenant_id',
        'kyb_verification_id',
        'overall_score',
        'risk_level',
        'sanctions_risk_score',
        'pep_risk_score',
        'adverse_media_risk_score',
        'financial_risk_score',
        'operational_risk_score',
        'geographic_risk_score',
        'risk_factors',
        'scoring_model',
        'scored_at',
    ];

    protected $casts = [
        'overall_score' => 'integer',
        'sanctions_risk_score' => 'integer',
        'pep_risk_score' => 'integer',
        'adverse_media_risk_score' => 'integer',
        'financial_risk_score' => 'integer',
        'operational_risk_score' => 'integer',
        'geographic_risk_score' => 'integer',
        'risk_factors' => 'json',
        'scored_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (app()->bound('tenant') && app('tenant') instanceof \App\Models\Tenant) {
                $query->where('tenant_id', app('tenant')->id);
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    public function kybVerification(): BelongsTo
    {
        return $this->belongsTo(KYBVerification::class);
    }

    public function isCritical(): bool
    {
        return $this->risk_level === 'critical';
    }

    public function isHigh(): bool
    {
        return $this->risk_level === 'high';
    }

    public function isLow(): bool
    {
        return $this->risk_level === 'low';
    }

    public function scopeByRiskLevel($query, string $level)
    {
        return $query->where('risk_level', $level);
    }

    public function scopeCritical($query)
    {
        return $query->where('risk_level', 'critical');
    }

    public function scopeHigh($query)
    {
        return $query->where('risk_level', 'high');
    }
}
