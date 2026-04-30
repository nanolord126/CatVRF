<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class UBOChain extends Model
{
    protected $fillable = [
        'tenant_id',
        'kyb_verification_id',
        'level',
        'entity_type',
        'entity_name',
        'entity_inn',
        'entity_ogrn',
        'ownership_percentage',
        'is_ultimate_beneficial_owner',
        'director_name',
        'director_inn',
        'entity_address',
        'registration_date',
        'sanctions_status',
        'pep_status',
        'raw_data',
    ];

    protected $casts = [
        'level' => 'integer',
        'ownership_percentage' => 'decimal:2',
        'is_ultimate_beneficial_owner' => 'boolean',
        'registration_date' => 'date',
        'raw_data' => 'json',
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

    public function sanctionsScreenings(): HasMany
    {
        return $this->hasMany(SanctionsScreening::class);
    }

    public function pepRecords(): HasMany
    {
        return $this->hasMany(PEPRecord::class);
    }

    public function adverseMediaAlerts(): HasMany
    {
        return $this->hasMany(AdverseMediaAlert::class);
    }

    public function scopeByLevel($query, int $level)
    {
        return $query->where('level', $level);
    }

    public function scopeUltimateBeneficialOwners($query)
    {
        return $query->where('is_ultimate_beneficial_owner', true);
    }

    public function scopeSanctioned($query)
    {
        return $query->where('sanctions_status', 'sanctioned');
    }

    public function scopePEP($query)
    {
        return $query->where('pep_status', 'pep');
    }
}
