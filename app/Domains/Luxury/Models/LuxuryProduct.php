<?php

declare(strict_types=1);

namespace App\Domains\Luxury\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * LuxuryProduct
 *
 * Layer 1: Model Layer
 * Описывает эксклюзивные товары: часы, сумки, ювелирные изделия.
 *
 * @version 1.0.0
 * @author CatVRF
 */
final class LuxuryProduct extends Model
{
    use SoftDeletes;

    protected $table = 'luxury_products';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'brand_id',
        'sku',
        'name',
        'description',
        'price_kopecks',
        'min_deposit_kopecks',
        'specifications',
        'current_stock',
        'hold_stock',
        'is_personalized',
        'tags',
        'correlation_id',
    ];

    protected $casts = [
        'specifications' => 'json',
        'tags' => 'json',
        'is_personalized' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->uuid = (string) Str::uuid();
            if (empty($model->tenant_id) && function_exists('tenant') && tenant()) {
                $model->tenant_id = tenant()->id;
            }
        });

        static::addGlobalScope('tenant', function (Builder $builder) {
            if (function_exists('tenant') && tenant()) {
                $builder->where('luxury_products.tenant_id', tenant()->id);
            }
        });
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(LuxuryBrand::class, 'brand_id');
    }
}
