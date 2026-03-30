<?php declare(strict_types=1);

namespace App\Domains\Food\Beverages\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BeverageShop extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use SoftDeletes;

        protected $table = 'beverage_shops';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'business_group_id',
            'correlation_id',
            'name',
            'type',
            'address',
            'geo_point',
            'schedule',
            'rating',
            'review_count',
            'is_active',
            'tags',
        ];

        protected $hidden = [
            'correlation_id',
        ];

        protected $casts = [
            'geo_point' => 'json',
            'schedule' => 'json',
            'tags' => 'json',
            'is_active' => 'boolean',
            'rating' => 'float',
        ];

        /**
         * Boot the model.
         */
        protected static function booted(): void
        {
            static::creating(function (Model $model) {
                $model->uuid = (string) Str::uuid();
                if (empty($model->correlation_id) && request()->hasHeader('X-Correlation-ID')) {
                    $model->correlation_id = request()->header('X-Correlation-ID');
                }
            });

            // 2026 Canon: Global Scope Tenant
            static::addGlobalScope('tenant', function (Builder $builder) {
                if (function_exists('tenant') && tenant() !== null) {
                    $builder->where('tenant_id', tenant()->id);
                }
            });
        }

        /**
         * Categories in the shop.
         */
        public function categories(): HasMany
        {
            return $this->hasMany(BeverageCategory::class, 'shop_id');
        }

        /**
         * Drinks in the shop.
         */
        public function drinks(): HasMany
        {
            return $this->hasMany(BeverageItem::class, 'shop_id');
        }

        /**
         * Orders in the shop.
         */
        public function orders(): HasMany
        {
            return $this->hasMany(BeverageOrder::class, 'shop_id');
        }

        /**
         * Active scope.
         */
        public function scopeActive(Builder $query): Builder
        {
            return $query->where('is_active', true);
        }
}
