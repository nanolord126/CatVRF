<?php declare(strict_types=1);

namespace App\Domains\Flowers\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FlowerProduct extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasFactory, SoftDeletes;

        protected $table = 'flower_products';

        protected $fillable = [
            'tenant_id',
            'shop_id',
            'name',
            'description',
            'images',
            'product_type',
            'flowers',
            'add_ons',
            'price',
            'stock',
            'min_order_days',
            'rating',
            'review_count',
            'orders_count',
            'is_available',
            'seasonal',
            'correlation_id',
            'tags',
        ];

        protected $casts = [
            'images' => 'json',
            'flowers' => 'json',
            'add_ons' => 'json',
            'tags' => 'json',
            'is_available' => 'boolean',
            'seasonal' => 'boolean',
            'price' => 'decimal:2',
            'rating' => 'float',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', function ($query) {
                if (filament()->getTenant()) {
                    $query->where('tenant_id', filament()->getTenant()->id);
                }
            });
        }

        public function shop(): BelongsTo
        {
            return $this->belongsTo(FlowerShop::class);
        }

        public function orderItems(): HasMany
        {
            return $this->hasMany(FlowerOrderItem::class, 'product_id');
        }
}
