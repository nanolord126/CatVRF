<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SanctionsScreening extends Model
{
    protected $fillable = [
        'tenant_id',
        'kyb_verification_id',
        'ubo_chain_id',
        'screening_type',
        'screening_provider',
        'entity_name',
        'entity_inn',
        'entity_dob',
        'entity_nationality',
        'screening_status',
        'match_score',
        'matched_sanction_list',
        'matched_sanction_entry',
        'screening_details',
        'screened_at',
        'correlation_id',
    ];

    protected $casts = [
        'match_score' => 'integer',
        'entity_dob' => 'date',
        'matched_sanction_entry' => 'json',
        'screening_details' => 'json',
        'screened_at' => 'datetime',
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

    public function uboChain(): BelongsTo
    {
        return $this->belongsTo(UBOChain::class);
    }

    public function hasMatch(): bool
    {
        return in_array($this->screening_status, ['match', 'potential_match'], true);
    }

    public function scopeByProvider($query, string $provider)
    {
        return $query->where('screening_provider', $provider);
    }

    public function scopeWithMatch($query)
    {
        return $query->whereIn('screening_status', ['match', 'potential_match']);
    }

    public function scopeClean($query)
    {
        return $query->where('screening_status', 'clean');
    }
}
