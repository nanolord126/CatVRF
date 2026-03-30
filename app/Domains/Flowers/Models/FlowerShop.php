<?php declare(strict_types=1);

namespace App\Domains\Flowers\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FlowerShop extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasFactory, SoftDeletes;

        protected $table = 'flower_shops';

        protected $fillable = [
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

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', function ($query) {
                if (filament()->getTenant()) {
                    $query->where('tenant_id', filament()->getTenant()->id);
                }
            });
        }

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
}
