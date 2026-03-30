<?php declare(strict_types=1);

namespace App\Domains\Food\Beverages\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BeverageItem extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use SoftDeletes;

        protected $table = 'beverage_items';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'category_id',
            'shop_id',
            'name',
            'description',
            'price',
            'volume_ml',
            'ingredients',
            'allergens',
            'nutritional_value',
            'stock_count',
            'freshness_control_type',
            'shelf_life_hours',
            'is_available',
            'correlation_id',
            'tags',
        ];

        protected $casts = [
            'ingredients' => 'json',
            'allergens' => 'json',
            'nutritional_value' => 'json',
            'tags' => 'json',
            'is_available' => 'boolean',
            'price' => 'integer',
            'volume_ml' => 'integer',
            'stock_count' => 'integer',
            'shelf_life_hours' => 'integer',
        ];

        /**
         * Boot the model.
         */
        protected static function booted(): void
        {
            static::creating(function (Model $model) {
                $model->uuid = (string) Str::uuid();
            });

            // 2026 Canon: Global Scope Tenant
            static::addGlobalScope('tenant', function (Builder $builder) {
                if (function_exists('tenant') && tenant() !== null) {
                    $builder->where('tenant_id', tenant()->id);
                }
            });
        }

        /**
         * Parent category.
         */
        public function category(): BelongsTo
        {
            return $this->belongsTo(BeverageCategory::class, 'category_id');
        }

        /**
         * Parent shop.
         */
        public function shop(): BelongsTo
        {
            return $this->belongsTo(BeverageShop::class, 'shop_id');
        }

        /**
         * Reviews for this item.
         */
        public function reviews(): HasMany
        {
            return $this->hasMany(BeverageReview::class, 'item_id');
        }

        /**
         * Scope for available items.
         */
        public function scopeAvailable(Builder $query): Builder
        {
            return $query->where('is_available', true);
        }

        /**
         * Price in human readable format ($XX.YY)
         */
        public function getPriceFormattedAttribute(): string
        {
            return number_format($this->price / 100, 2, '.', ',');
        }
}
