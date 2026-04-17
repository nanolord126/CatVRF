<?php

declare(strict_types=1);

namespace App\Domains\CRM\Models;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CrmAutoProfile — CRM-профиль клиента вертикали Авто.
 *
 * VIN, пробег, история ТО, страховки, предпочтения запчастей.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class CrmAutoProfile extends Model
{


    protected static function newFactory(): \Database\Factories\CRM\CrmAutoProfileFactory
    {
        return \Database\Factories\CRM\CrmAutoProfileFactory::new();
    }
    protected $table = 'crm_auto_profiles';

    protected $fillable = [
        'crm_client_id', 'tenant_id', 'vin', 'car_brand', 'car_model',
        'car_year', 'car_color', 'mileage_km', 'engine_type', 'transmission',
        'insurance_expires_at', 'next_service_at', 'service_history',
        'preferred_parts_brands', 'car_preferences', 'drivers_license_category',
        'has_garage', 'notes', 'correlation_id',
    ];

    protected $casts = [
        'service_history' => 'json',
        'preferred_parts_brands' => 'json',
        'car_preferences' => 'json',
        'insurance_expires_at' => 'date',
        'next_service_at' => 'date',
        'has_garage' => 'boolean',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(CrmClient::class, 'crm_client_id');
    }

    public function __toString(): string
    {
        return sprintf('CrmAutoProfile[id=%d, vin=%s]', $this->id ?? 0, $this->vin ?? '');
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
