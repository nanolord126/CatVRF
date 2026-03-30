<?php declare(strict_types=1);

namespace App\Domains\Fashion\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FashionProductVariant extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use SoftDeletes;

        protected $table = 'fashion_product_variants';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'product_id',
            'sku_variant',
            'color',
            'size',
            'price_adjustment',
            'current_stock',
            'reserved_stock',
            'images',
            'correlation_id',
        ];

        protected $casts = [
            'images' => 'collection',
            'price_adjustment' => 'float',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant_id', function ($query) {
                if (tenant('id')) {
                    $query->where('tenant_id', tenant('id'));
                }
            });
        }

        public function product(): BelongsTo
        {
            return $this->belongsTo(FashionProduct::class, 'product_id');
        }
}
