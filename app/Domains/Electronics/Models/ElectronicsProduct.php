<?php declare(strict_types=1);

namespace App\Domains\Electronics\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'category',
        'type',
        'model_number',
        'description',
        'price_kopecks',
        'original_price_kopecks',
        'b2b_price_kopecks',
        'current_stock',
        'stock_quantity',
        'hold_stock',
        'min_threshold',
        'availability',
        'availability_status',
        'is_active',
        'specs',
        'color',
        'images',
        'package_contents',
        'weight_kg',
        'rating',
        'reviews_count',
        'views_count',
        'is_bestseller',
        'correlation_id',
        'tags',
    ];

    /**
     * Type casts.
     */
    protected $casts = [
        'price_kopecks' => 'integer',
        'original_price_kopecks' => 'integer',
        'b2b_price_kopecks' => 'integer',
        'current_stock' => 'integer',
        'stock_quantity' => 'integer',
        'hold_stock' => 'integer',
        'is_active' => 'boolean',
        'is_bestseller' => 'boolean',
        'specs' => 'json',
        'images' => 'json',
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
