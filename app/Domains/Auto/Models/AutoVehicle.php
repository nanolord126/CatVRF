<?php

declare(strict_types=1);

namespace App\Domains\Auto\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * AutoVehicle Model — Канон 2026.
 * 
 * База данных автомобилей клиентов платформы.
 * Идентификатор: VIN-код (17 символов).
 * Поддержка UUID и Tenant Scoping.
 */
final class AutoVehicle extends Model
{
    use SoftDeletes;

    protected $table = 'auto_vehicles';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'auto_catalog_brand_id',
        'owner_id',
        'owner_type',
        'vin',
        'model',
        'year_produced',
        'engine_code',
        'license_plate',
        'current_mileage',
        'correlation_id',
        'tags',
        'metadata',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'auto_catalog_brand_id' => 'integer',
        'owner_id' => 'integer',
        'year_produced' => 'integer',
        'current_mileage' => 'integer',
        'tags' => 'json',
        'metadata' => 'json',
    ];

    /**
     * Автоматическая генерация UUID и tenant scoping.
     */
    protected static function booted(): void
    {
        static::creating(function ($model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
            $model->tenant_id = $model->tenant_id ?? (tenant('id') ?? 1);
        });

        static::addGlobalScope('tenant_id', function ($query) {
            $query->where('tenant_id', tenant('id') ?? 1);
        });
    }

    /**
     * Отношение к бренду (Марке авто).
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(AutoCatalogBrand::class, 'auto_catalog_brand_id');
    }

    /**
     * Отношение к заказ-нарядам (Ремонтам).
     */
    public function repairOrders(): HasMany
    {
        return $this->hasMany(AutoRepairOrder::class, 'auto_vehicle_id');
    }

    /**
     * Проверка на корректность VIN.
     */
    public function isValidVin(): bool
    {
        return strlen($this->vin) === 17;
    }
}
