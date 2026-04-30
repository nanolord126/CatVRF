<?php

declare(strict_types=1);

namespace App\Domains\CRM\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Database\Factories\CRM\CrmAutoProfileFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * CrmAutoProfile — CRM-профиль клиента вертикали Авто.
 *
 * VIN, пробег, история ТО, страховки, предпочтения запчастей.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class CrmAutoProfile extends Model
{
    use TenantScoped;

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
     * Scope: только активные записи.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    protected static function newFactory(): CrmAutoProfileFactory
    {
        return CrmAutoProfileFactory::new();
    }

    /**
     * Boot методы модели — global scopes и auto-UUID.
     */
    protected static function booted(): void
    {
        self::addGlobalScope('tenant', function ($query): void {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });

        self::creating(function ($model): void {
            if (! $model->uuid && $model->isFillable('uuid')) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }
}
