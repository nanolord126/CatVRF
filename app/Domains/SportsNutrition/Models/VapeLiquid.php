<?php

declare(strict_types=1);

namespace App\Domains\Vapes\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * VapeLiquid Model — Production Ready 2026
 * 
 * Жидкости для парения. Контроль крепости никотина, GTIN и маркировки "Честный ЗНАК".
 * 
 * @property string $uuid
 * @property int $tenant_id
 * @property int $brand_id
 * @property string $flavor_name
 * @property int $volume_ml
 * @property int $nicotine_strength (mg/ml)
 * @property string $nicotine_type (salt, classic)
 * @property bigInteger $price_kopecks
 * @property string|null $gtin
 */
final class VapeLiquid extends Model
{
    use SoftDeletes;

    protected $table = 'vapes_liquids';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'brand_id',
        'flavor_name',
        'volume_ml',
        'nicotine_strength',
        'nicotine_type',
        'price_kopecks',
        'current_stock',
        'gtin',
        'marking_code_template',
        'correlation_id',
    ];

    protected $casts = [
        'volume_ml' => 'integer',
        'nicotine_strength' => 'integer',
        'price_kopecks' => 'integer',
        'current_stock' => 'integer',
        'tenant_id' => 'integer',
        'brand_id' => 'integer',
    ];

    protected $hidden = [
        'id',
        'deleted_at',
    ];

    /**
     * Booted method for global scoping and UUID generation.
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
        static::creating(function (VapeLiquid $model) {
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
     * Бренд-производитель жидкости.
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(VapeBrand::class, 'brand_id');
    }

    /**
     * Проверка на отсутствие никотина (безникотиновые жидкости).
     */
    public function isNicotineFree(): bool
    {
        return $this->nicotine_strength === 0;
    }

    /**
     * Проверка: требует ли жидкость маркировки Честным Зннаком (в РФ все никотиновые должны иметь).
     */
    public function requiresMarking(): bool
    {
        return $this->nicotine_strength > 0;
    }
}
