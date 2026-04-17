<?php declare(strict_types=1);

namespace App\Domains\MeatShops\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MeatShop extends Model
{

    use HasUuids, SoftDeletes, TenantScoped;

        protected $table = 'meat_shops';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'business_group_id',
            'correlation_id',
            'name',
            'owner_id',
            'description',
            'address',
            'phone',
            'latitude',
            'longitude',
            'certification_number',
            'is_verified',
            'commission_percent',
            'delivery_zones',
            'min_order_amount',
            'schedule',
            'tags',
        ];

        protected $casts = [
            'is_verified' => 'boolean',
            'commission_percent' => 'float',
            'latitude' => 'float',
            'longitude' => 'float',
            'min_order_amount' => 'integer',
            'delivery_zones' => 'json',
            'schedule' => 'json',
            'tags' => 'json',
        ];

        public function products()
        {
            return $this->hasMany(MeatProduct::class, 'meat_shop_id');
        }

        public function orders()
        {
            return $this->hasMany(MeatOrder::class, 'meat_shop_id');
        }

        public function subscriptions()
        {
            return $this->hasMany(MeatBoxSubscription::class, 'meat_shop_id');
        }

        protected static function booted_disabled(): void
        {
            static::addGlobalScope('tenant', function ($query) {
                $query->where('meat_shops.tenant_id', tenant()->id);
            });
        }

        public function getTotalOrders(): int
        {
            return $this->orders()->count();
        }

        public function getTotalRevenue(): int
        {
            return (int) $this->orders()->where('status', 'completed')->sum('total_price_kopecks');
        }
}
