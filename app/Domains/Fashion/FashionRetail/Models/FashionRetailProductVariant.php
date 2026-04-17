<?php declare(strict_types=1);

namespace App\Domains\Fashion\FashionRetail\Models;
use Illuminate\Database\Eloquent\SoftDeletes;

use Carbon\Carbon;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FashionRetailProductVariant extends Model
{


        protected $table = 'fashion_retail_product_variants';

        protected $fillable = [
        'correlation_id',
            'uuid',
            'product_id',
            'color',
            'size',
            'sku',
            'price',
            'cost_price',
            'current_stock',
            'min_stock_threshold',
            'images',
            'status',
            'tags',
        ];

        protected $casts = [
            'images' => 'json',
            'tags' => 'json',
            'price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'current_stock' => 'integer',
            'min_stock_threshold' => 'integer',
        ];

        public function product(): BelongsTo
        {
            return $this->belongsTo(FashionRetailProduct::class, 'product_id');
        }

        protected static function booted(): void
        {
            parent::booted();
            static::addGlobalScope("tenant_id", function ($query) {
                if (function_exists("tenant") && tenant("id")) {
                    $query->where("tenant_id", tenant("id"));
                }
            });
        }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return static::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'timestamp' => Carbon::now()->toIso8601String(),
        ];
    }
}
