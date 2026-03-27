<?php declare(strict_types=1);

namespace App\Domains\Fashion\FashionRetail\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class FashionRetailProduct extends Model
{
    use SoftDeletes;

    protected $table = 'fashion_retail_products';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'shop_id',
        'category_id',
        'name',
        'description',
        'sku',
        'barcode',
        'price',
        'cost_price',
        'discount_percent',
        'current_stock',
        'min_stock_threshold',
        'colors',
        'sizes',
        'images',
        'supplier_id',
        'rating',
        'review_count',
        'status',
        'tags',
        'correlation_id',
    ];

    protected $casts = [
        'colors' => 'json',
        'sizes' => 'json',
        'images' => 'json',
        'tags' => 'json',
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'discount_percent' => 'integer',
        'current_stock' => 'integer',
        'min_stock_threshold' => 'integer',
        'rating' => 'float',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant_id', function ($query) {
            if (tenant('id')) {
                $query->where('tenant_id', tenant('id'));
            }
        });
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(FashionRetailShop::class, 'shop_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(FashionRetailCategory::class, 'category_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(FashionRetailProductVariant::class, 'product_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(FashionRetailOrder::class, 'product_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(FashionRetailReview::class, 'product_id');
    }
}
