<?php

declare(strict_types=1);

namespace App\Domains\CRM\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * CrmElectronicsProfile — CRM-профиль клиента вертикали Электроника.
 *
 * Устройства, гарантии, trade-in, предпочтения брендов, ОС.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class CrmElectronicsProfile extends Model
{
    use TenantScoped;

    protected $table = 'crm_electronics_profiles';

    protected $fillable = [
        'crm_client_id', 'tenant_id', 'owned_devices', 'preferred_brands',
        'preferred_categories', 'tech_level', 'wishlist', 'warranty_tracking',
        'trade_in_history', 'repair_history', 'preferred_os', 'preferred_price_range',
        'interested_in_trade_in', 'wants_extended_warranty', 'subscribed_to_new_releases',
        'avg_purchase_amount', 'notes', 'correlation_id',
    ];

    protected $casts = [
        'owned_devices' => 'json',
        'preferred_brands' => 'json',
        'preferred_categories' => 'json',
        'wishlist' => 'json',
        'warranty_tracking' => 'json',
        'trade_in_history' => 'json',
        'repair_history' => 'json',
        'interested_in_trade_in' => 'boolean',
        'wants_extended_warranty' => 'boolean',
        'subscribed_to_new_releases' => 'boolean',
        'avg_purchase_amount' => 'decimal:2',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(CrmClient::class, 'crm_client_id');
    }

    public function __toString(): string
    {
        return sprintf('CrmElectronicsProfile[id=%d, os=%s]', $this->id ?? 0, $this->preferred_os ?? '');
    }

    /**
     * Scope: только активные записи.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
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
