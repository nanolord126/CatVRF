<?php declare(strict_types=1);

namespace App\Domains\FashionRetail\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class FashionRetailProductVariant extends Model
{
    use SoftDeletes;

    protected $table = 'fashion_retail_product_variants';

    protected $fillable = [
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
}
