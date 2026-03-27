<?php

declare(strict_types=1);

namespace App\Domains\Electronics\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * ElectronicsProduct - The core model for gadgets and accessories.
 * Requirement: Final class, strict types, tenant scoping.
 */
final class ElectronicsProduct extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'electronics_products';

    /**
     * Mass assignable attributes.
     */
    protected $fillable = [
        'uuid',
        'tenant_id',
        'category_id',
        'store_id',
        'name',
        'sku',
        'brand',
        'model_number',
        'description',
        'price_kopecks',
        'b2b_price_kopecks',
        'current_stock',
        'hold_stock',
        'min_threshold',
        'availability',
        'specs',
        'package_contents',
        'weight_kg',
        'correlation_id',
        'tags',
    ];

    /**
     * Type casts.
     */
    protected $casts = [
        'price_kopecks' => 'integer',
        'b2b_price_kopecks' => 'integer',
        'current_stock' => 'integer',
        'hold_stock' => 'integer',
        'specs' => 'json',
        'package_contents' => 'json',
        'weight_kg' => 'float',
        'tags' => 'json',
    ];

    /**
     * Global Scope: Tenant Isolation.
     */
    protected static function booted(): void
    {
        static::creating(function (Model $model) {
            $model->uuid = $model->uuid ?: (string) Str::uuid();
            $model->tenant_id = $model->tenant_id ?: (tenant()->id ?? 0);
        });

        static::addGlobalScope('tenant', function (Builder $builder) {
            if (tenant()) {
                $builder->where('tenant_id', tenant()->id);
            }
        });
    }

    /* --- Relations --- */

    public function category(): BelongsTo
    {
        return $this->belongsTo(ElectronicsCategory::class, 'category_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(ElectronicsStore::class, 'store_id');
    }

    public function gadget(): HasMany
    {
        return $this->hasMany(ElectronicsGadget::class, 'product_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ElectronicsReview::class, 'product_id');
    }

    public function warranties(): HasMany
    {
        return $this->hasMany(ElectronicsWarranty::class, 'product_id');
    }

    /* --- Scopes --- */

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('availability', 'in_stock')
                     ->where('current_stock', '>', 0);
    }

    public function scopeB2B(Builder $query): Builder
    {
        return $query->whereNotNull('b2b_price_kopecks');
    }

    /* --- Helpers --- */

    public function getInStockCountAttribute(): int
    {
        return $this->current_stock - $this->hold_stock;
    }
}
