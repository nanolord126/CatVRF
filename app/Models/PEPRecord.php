<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PEPRecord extends Model
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
        'pep_status',
        'pep_position',
        'pep_country',
        'pep_start_date',
        'pep_end_date',
        'pep_category',
        'screening_details',
        'screened_at',
        'correlation_id',
    ];

    protected $casts = [
        'entity_dob' => 'date',
        'pep_start_date' => 'date',
        'pep_end_date' => 'date',
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

    public function isPEP(): bool
    {
        return $this->pep_status === 'pep';
    }

    public function isFormerPEP(): bool
    {
        return $this->pep_status === 'former_pep';
    }

    public function scopePEP($query)
    {
        return $query->where('pep_status', 'pep');
    }

    public function scopeFormerPEP($query)
    {
        return $query->where('pep_status', 'former_pep');
    }

    public function scopeByCountry($query, string $country)
    {
        return $query->where('pep_country', $country);
    }
}
