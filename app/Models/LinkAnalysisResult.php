<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class LinkAnalysisResult extends Model
{
    protected $fillable = [
        'tenant_id',
        'kyb_verification_id',
        'circular_ownership',
        'shell_companies',
        'money_laundering_patterns',
        'beneficiary_clusters',
        'network_risk_score',
        'network_risk_level',
        'network_risk_factors',
        'analyzed_at',
        'correlation_id',
    ];

    protected $casts = [
        'circular_ownership' => 'json',
        'shell_companies' => 'json',
        'money_laundering_patterns' => 'json',
        'beneficiary_clusters' => 'json',
        'network_risk_factors' => 'json',
        'analyzed_at' => 'datetime',
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

    public function scopeByRiskLevel($query, string $level)
    {
        return $query->where('network_risk_level', $level);
    }

    public function scopeCriticalRisk($query)
    {
        return $query->where('network_risk_level', 'critical');
    }

    public function scopeHighRisk($query)
    {
        return $query->whereIn('network_risk_level', ['critical', 'high']);
    }

    public function hasCycles(): bool
    {
        return ! empty($this->circular_ownership);
    }

    public function hasShellCompanies(): bool
    {
        return ! empty($this->shell_companies);
    }

    public function hasMoneyLaunderingPatterns(): bool
    {
        return ! empty($this->money_laundering_patterns);
    }
}
