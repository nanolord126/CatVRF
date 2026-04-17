<?php

declare(strict_types=1);

namespace App\Domains\CRM\Models;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CrmRealEstateProfile — CRM-профиль клиента вертикали Недвижимость.
 *
 * Объекты интереса, бюджет, ипотека, просмотры, сделки.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class CrmRealEstateProfile extends Model
{

    protected $table = 'crm_realestate_profiles';

    protected $fillable = [
        'crm_client_id', 'tenant_id', 'client_role', 'budget_min', 'budget_max',
        'preferred_locations', 'property_requirements', 'property_type_preference',
        'mortgage_needed', 'mortgage_approved', 'mortgage_bank', 'mortgage_amount',
        'viewed_properties', 'saved_properties', 'viewings_count', 'desired_move_date',
        'deal_history', 'notes', 'correlation_id',
    ];

    protected $casts = [
        'preferred_locations' => 'json',
        'property_requirements' => 'json',
        'viewed_properties' => 'json',
        'saved_properties' => 'json',
        'deal_history' => 'json',
        'mortgage_needed' => 'boolean',
        'mortgage_approved' => 'boolean',
        'budget_min' => 'decimal:2',
        'budget_max' => 'decimal:2',
        'mortgage_amount' => 'decimal:2',
        'desired_move_date' => 'date',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(CrmClient::class, 'crm_client_id');
    }

    public function __toString(): string
    {
        return sprintf('CrmRealEstateProfile[id=%d, role=%s]', $this->id ?? 0, $this->client_role ?? '');
    }

    /**
     * Boot методы модели — global scopes и auto-UUID.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query): void {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });

        static::creating(function ($model): void {
            if (!$model->uuid && $model->isFillable('uuid')) {
                $model->uuid = \Illuminate\Support\Str::uuid()->toString();
            }
        });
    }

    /**
     * Scope: только активные записи.
     */
    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_active', true);
    }
}
