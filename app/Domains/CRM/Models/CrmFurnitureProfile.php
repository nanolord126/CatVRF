<?php

declare(strict_types=1);

namespace App\Domains\CRM\Models;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CrmFurnitureProfile — CRM-профиль клиента вертикали Мебель/Ремонт.
 *
 * Стиль интерьера, бюджет ремонта, замеры, этапы работ.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class CrmFurnitureProfile extends Model
{
use \Illuminate\Database\Eloquent\SoftDeletes;

    protected $table = 'crm_furniture_profiles';

    protected $fillable = [
        'crm_client_id', 'tenant_id', 'interior_style', 'preferred_materials',
        'preferred_colors', 'room_dimensions', 'renovation_budget', 'property_type',
        'property_area_sqm', 'rooms_count', 'renovation_stages', 'purchased_items_history',
        'needs_delivery', 'needs_assembly', 'needs_design_project', 'measurements_data',
        'notes', 'correlation_id',
    ];

    protected $casts = [
        'preferred_materials' => 'json',
        'preferred_colors' => 'json',
        'room_dimensions' => 'json',
        'renovation_stages' => 'json',
        'purchased_items_history' => 'json',
        'measurements_data' => 'json',
        'renovation_budget' => 'decimal:2',
        'needs_delivery' => 'boolean',
        'needs_assembly' => 'boolean',
        'needs_design_project' => 'boolean',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(CrmClient::class, 'crm_client_id');
    }

    public function __toString(): string
    {
        return sprintf('CrmFurnitureProfile[id=%d, style=%s]', $this->id ?? 0, $this->interior_style ?? '');
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
