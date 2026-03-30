<?php declare(strict_types=1);

namespace App\Domains\Food\Beverages\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BeverageCategory extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected $table = 'beverage_categories';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'shop_id',
            'name',
            'description',
            'sort_order',
            'correlation_id',
        ];

        protected $casts = [
            'sort_order' => 'integer',
            'tenant_id' => 'string',
            'shop_id' => 'integer',
            'uuid' => 'string',
            'correlation_id' => 'string',
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
         * Parent shop.
         */
        public function shop(): BelongsTo
        {
            return $this->belongsTo(BeverageShop::class, 'shop_id');
        }

        /**
         * Items in this category.
         */
        public function items(): HasMany
        {
            return $this->hasMany(BeverageItem::class, 'category_id')->orderBy('sort_order', 'asc');
        }

        /**
         * Scope for sorted categories.
         */
        public function scopeSorted(Builder $query): Builder
        {
            return $query->orderBy('sort_order', 'asc');
        }
}
