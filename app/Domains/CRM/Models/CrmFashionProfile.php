<?php

declare(strict_types=1);

namespace App\Domains\CRM\Models;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CrmFashionProfile — CRM-профиль клиента вертикали Fashion.
 *
 * Размеры, цветотип, стиль, капсулы, wishlist, AR-примерки.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class CrmFashionProfile extends Model
{

    protected $table = 'crm_fashion_profiles';

    protected $fillable = [
        'crm_client_id', 'tenant_id', 'body_type', 'color_type', 'style_type',
        'sizes', 'preferred_brands', 'preferred_colors', 'disliked_styles',
        'wardrobe_capsules', 'wishlist', 'ar_tryons_count', 'ar_tryons_history',
        'avg_purchase_amount', 'preferred_price_range', 'seasonal_preferences',
        'notes', 'correlation_id',
    ];

    protected $casts = [
        'sizes' => 'json',
        'preferred_brands' => 'json',
        'preferred_colors' => 'json',
        'disliked_styles' => 'json',
        'wardrobe_capsules' => 'json',
        'wishlist' => 'json',
        'ar_tryons_history' => 'json',
        'seasonal_preferences' => 'json',
        'avg_purchase_amount' => 'decimal:2',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(CrmClient::class, 'crm_client_id');
    }

    public function __toString(): string
    {
        return sprintf('CrmFashionProfile[id=%d, color_type=%s]', $this->id ?? 0, $this->color_type ?? '');
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
