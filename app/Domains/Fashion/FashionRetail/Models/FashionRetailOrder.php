<?php declare(strict_types=1);

namespace App\Domains\Fashion\FashionRetail\Models;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FashionRetailOrder extends Model
{


        protected $table = 'fashion_retail_orders';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'business_group_id',
            'user_id',
            'shop_id',
            'order_number',
            'items',
            'total_amount',
            'discount_amount',
            'commission_amount',
            'delivery_fee',
            'status',
            'payment_status',
            'delivery_address',
            'delivery_method',
            'tracking_number',
            'notes',
            'correlation_id',
            'tags',
        ];

        protected $casts = [
            'items' => 'json',
            'total_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
            'tags' => 'json',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant_id', function ($query) {
                if (tenant()->id) {
                    $query->where('tenant_id', tenant()->id);
                }
            });
        }

        public function shop(): BelongsTo
        {
            return $this->belongsTo(FashionRetailShop::class, 'shop_id');
        }

        public function user(): BelongsTo
        {
            return $this->belongsTo(\App\Models\User::class);
        }

        public function returns(): HasMany
        {
            return $this->hasMany(FashionRetailReturn::class, 'order_id');
        }
}
