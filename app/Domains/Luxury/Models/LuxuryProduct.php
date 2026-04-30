<?php

declare(strict_types=1);

namespace App\Domains\Luxury\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class LuxuryProduct extends Model
{
    use TenantScoped;

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

    public function brand(): BelongsTo
    {
        return $this->belongsTo(LuxuryBrand::class, 'brand_id');
    }

    protected static function booted_disabled(): void
    {
        self::creating(function (self $model) {
            $model->uuid = (string) Str::uuid();
            if (empty($model->tenant_id) && function_exists('tenant') && tenant()) {
                $model->tenant_id = tenant()->id;
            }
        });

        self::addGlobalScope('tenant', function (Builder $builder) {
            if (function_exists('tenant') && tenant()) {
                $builder->where('luxury_products.tenant_id', tenant()->id);
            }
        });
    }
}
