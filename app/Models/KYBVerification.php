<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class KYBVerification extends Model
{
    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'inn',
        'verification_status',
        'risk_score',
        'risk_level',
        'ubo_chain',
        'kyb_data',
        'sanctions_screening',
        'pep_screening',
        'adverse_media',
        'verification_provider',
        'verified_at',
        'expires_at',
        'manual_review_required',
        'manual_review_assigned_to',
        'manual_review_notes',
        'manual_reviewed_at',
        'correlation_id',
    ];

    protected $casts = [
        'risk_score' => 'integer',
        'ubo_chain' => 'json',
        'kyb_data' => 'json',
        'sanctions_screening' => 'json',
        'pep_screening' => 'json',
        'adverse_media' => 'json',
        'verified_at' => 'datetime',
        'expires_at' => 'datetime',
        'manual_review_required' => 'boolean',
        'manual_reviewed_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function businessGroup(): BelongsTo
    {
        return $this->belongsTo(BusinessGroup::class);
    }

    public function uboChains(): HasMany
    {
        return $this->hasMany(UBOChain::class);
    }

    public function sanctionsScreenings(): HasMany
    {
        return $this->hasMany(SanctionsScreening::class);
    }

    public function riskScores(): HasMany
    {
        return $this->hasMany(BusinessRiskScore::class);
    }

    public function isApproved(): bool
    {
        return $this->verification_status === 'approved';
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function requiresManualReview(): bool
    {
        return $this->manual_review_required;
    }

    public function scopePendingReview($query)
    {
        return $query->where('manual_review_required', true)
            ->whereIn('verification_status', ['requires_review', 'in_progress']);
    }

    public function scopeApproved($query)
    {
        return $query->where('verification_status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('verification_status', 'rejected');
    }

    public function scopeByRiskLevel($query, string $level)
    {
        return $query->where('risk_level', $level);
    }
}
