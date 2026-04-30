<?php

declare(strict_types=1);

namespace App\Domains\Leisure\SubVerticals\Flowers\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FlowerShop extends Model
{
    use HasFactory;
    use SoftDeletes;
    use TenantScoped;

    protected $table = 'flower_shops';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'tenant_id',
        'user_id',
        'business_group_id',
        'shop_name',
        'description',
        'phone',
        'address',
        'location',
        'schedule',
        'delivery_radius_km',
        'delivery_fee',
        'rating',
        'review_count',
        'orders_count',
        'is_verified',
        'is_active',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'location' => 'json',
        'schedule' => 'json',
        'tags' => 'json',
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'rating' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(FlowerProduct::class, 'shop_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(FlowerOrder::class, 'shop_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(FlowerDelivery::class, 'shop_id');
    }

    public function b2bStorefronts(): HasMany
    {
        return $this->hasMany(B2BFlowerStorefront::class, 'shop_id');
    }

    public function b2bOrders(): HasMany
    {
        return $this->hasMany(B2BFlowerOrder::class, 'shop_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(FlowerReview::class, 'shop_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(FlowerSubscription::class, 'shop_id');
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
