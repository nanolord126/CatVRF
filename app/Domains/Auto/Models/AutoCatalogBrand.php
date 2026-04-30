<?php

declare(strict_types=1);

namespace App\Domains\Auto\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

final class AutoCatalogBrand extends Model
{
    use TenantScoped;

    protected $table = 'auto_catalog_brands';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'name',
        'slug',
        'type',
        'country',
        'correlation_id',
        'tags',
        'metadata',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'tags' => 'json',
        'metadata' => 'json',
    ];

    /**
     * Отношение к запчастям.
     */
    public function parts(): HasMany
    {
        return $this->hasMany(AutoPart::class, 'auto_catalog_brand_id');
    }

    /**
     * Отношение к автомобилям.
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(AutoVehicle::class, 'auto_catalog_brand_id');
    }

    /**
     * Автоматическая генерация UUID и tenant scoping.
     */
    protected static function booted(): void
    {
        self::creating(function ($model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
            $model->tenant_id = $model->tenant_id ?? (tenant()->id ?? 1);
        });

        self::addGlobalScope('tenant_id', function ($query) {
            $query->where('tenant_id', tenant()->id ?? 1);
        });
    }
}
