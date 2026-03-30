<?php declare(strict_types=1);

namespace App\Domains\Fashion\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FashionProduct extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use SoftDeletes;

        protected $table = 'fashion_products';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'fashion_store_id',
            'name',
            'description',
            'sku',
            'brand',
            'color',
            'material',
            'price_b2c',
            'price_b2b',
            'old_price',
            'stock_quantity',
            'reserve_quantity',
            'images',
            'attributes',
            'status',
            'correlation_id',
            'tags',
        ];

        protected $casts = [
            'images' => 'json',
            'attributes' => 'json',
            'tags' => 'json',
            'price_b2c' => 'integer',
            'price_b2b' => 'integer',
            'old_price' => 'integer',
            'stock_quantity' => 'integer',
            'reserve_quantity' => 'integer',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant_id', function (Builder $builder) {
                $tenantId = filament()->getTenant()?->id ?? auth()->user()?->tenant_id;
                if ($tenantId) {
                    $builder->where('tenant_id', $tenantId);
                }
            });

            /**
             * КАНЬОН: ЛОГИКА ЦЕНЫ 2026
             * Если цена выросла — обновляем. Если упала — оставляем старую в Product,
             * а разницу выводим как скидку (old_price).
             */
            static::updating(function ($model) {
                if ($model->isDirty('price_b2c')) {
                    $newPrice = (int)$model->price_b2c;
                    $oldPrice = (int)$model->getOriginal('price_b2c');

                    if ($newPrice < $oldPrice) {
                        $model->old_price = $oldPrice;
                    }
                }
            });
        }

        /**
         * Считает доступный остаток (Current - Reserved)
         */
        public function getAvailableStockAttribute(): int
        {
            return max(0, $this->stock_quantity - $this->reserve_quantity);
        }

        /**
         * Возвращает true, если товар в наличии
         */
        public function getIsInStockAttribute(): bool
        {
            return $this->available_stock > 0;
        }

        public function store(): BelongsTo
        {
            return $this->belongsTo(FashionStore::class, 'fashion_store_id');
        }

        public function sizes(): HasMany
        {
            return $this->hasMany(FashionSize::class, 'fashion_product_id');
        }

        public function reviews(): HasMany
        {
            return $this->hasMany(FashionReview::class, 'fashion_product_id');
        }
    }
            'sizes',
            'images',
            'attributes',
            'rating',
            'review_count',
            'status',
            'tags',
            'correlation_id',
        ];

        protected $casts = [
            'colors' => 'collection',
            'sizes' => 'collection',
            'images' => 'collection',
            'attributes' => 'collection',
            'tags' => 'collection',
            'price' => 'float',
            'cost_price' => 'float',
            'discount_percent' => 'float',
            'discount_price' => 'float',
            'rating' => 'float',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant_id', function ($query) {
                if (tenant('id')) {
                    $query->where('tenant_id', tenant('id'));
                }
            });
        }

        public function store(): BelongsTo
        {
            return $this->belongsTo(FashionStore::class, 'fashion_store_id');
        }

        public function category(): BelongsTo
        {
            return $this->belongsTo(FashionCategory::class);
        }

        public function variants(): HasMany
        {
            return $this->hasMany(FashionProductVariant::class, 'product_id');
        }

        public function reviews(): HasMany
        {
            return $this->hasMany(FashionReview::class, 'product_id');
        }

        public function wishlists(): HasMany
        {
            return $this->hasMany(FashionWishlist::class, 'product_id');
        }
}
