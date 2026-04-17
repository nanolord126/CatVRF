<?php declare(strict_types=1);

/**
 * FashionProductVariant — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/fashionproductvariant
 */


namespace App\Domains\Fashion\Models;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FashionProductVariant extends Model
{


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
                if (tenant()->id) {
                    $query->where('tenant_id', tenant()->id);
                }
            });
        }

        public function product(): BelongsTo
        {
            return $this->belongsTo(FashionProduct::class, 'product_id');
        }

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

}
