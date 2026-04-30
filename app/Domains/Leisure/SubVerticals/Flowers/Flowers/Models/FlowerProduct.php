<?php

declare(strict_types=1);

namespace App\Domains\Leisure\SubVerticals\Flowers\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FlowerProduct extends Model
{
    use HasFactory;
    use SoftDeletes;
    use TenantScoped;

    protected $table = 'flower_products';

    protected $fillable = [
        'uuid',
        'correlation_id',
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

    public function shop(): BelongsTo
    {
        return $this->belongsTo(FlowerShop::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(FlowerOrderItem::class, 'product_id');
    }

    protected static function booted(): void
    {
        self::addGlobalScope('tenant', function ($query) {
            if (tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });
    }
}
