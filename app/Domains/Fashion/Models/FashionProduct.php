<?php declare(strict_types=1);

namespace App\Domains\Fashion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class FashionProduct extends Model
{
    use SoftDeletes;

    protected $table = 'fashion_products';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'fashion_store_id',
        'category_id',
        'name',
        'description',
        'sku',
        'price',
        'cost_price',
        'discount_percent',
        'discount_price',
        'current_stock',
        'min_stock_threshold',
        'colors',
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
