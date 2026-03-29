<?php

declare(strict_types=1);

namespace App\Domains\Vapes\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * VapeBrand Model — Production Ready 2026
 * 
 * Бренды вейп-продукции.
 * 
 * @property string $uuid
 * @property int $tenant_id
 * @property string $name
 */
final class VapeBrand extends Model
{
    use SoftDeletes;

    protected $table = 'vapes_brands';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'name',
        'country_code',
        'description',
        'metadata',
        'correlation_id',
    ];

    protected $casts = [
        'metadata' => 'json',
        'tenant_id' => 'integer',
    ];

    protected $hidden = [
        'id',
        'deleted_at',
    ];

    /**
     * Booted method for global scoping and data protection.
     */
    protected static function booted(): void
    {
        // Изоляция данных на уровне базы (Tenant Scoping Канон 2026)
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (function_exists('tenant') && tenant('id')) {
                $builder->where('tenant_id', (int) tenant('id'));
            }
        });

        // Автогенерация UUID и Correlation ID
        static::creating(function (VapeBrand $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->correlation_id)) {
                $model->correlation_id = (string) Str::uuid();
            }
            if (empty($model->tenant_id) && function_exists('tenant')) {
                $model->tenant_id = (int) tenant('id');
            }
        });
    }

    /**
     * Все девайсы бренда.
     */
    public function devices(): HasMany
    {
        return $this->hasMany(VapeDevice::class, 'brand_id');
    }

    /**
     * Все жидкости бренда.
     */
    public function liquids(): HasMany
    {
        return $this->hasMany(VapeLiquid::class, 'brand_id');
    }
}
