<?php declare(strict_types=1);

namespace App\Domains\Fashion\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FashionOrder extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use SoftDeletes;

        protected $table = 'fashion_orders';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'fashion_store_id',
            'customer_id',
            'order_number',
            'subtotal',
            'discount_amount',
            'shipping_cost',
            'total_amount',
            'commission_amount',
            'status',
            'payment_status',
            'shipping_address',
            'billing_address',
            'tracking_number',
            'shipped_at',
            'delivered_at',
            'cancelled_at',
            'cancellation_reason',
            'transaction_id',
            'items',
            'tags',
            'correlation_id',
        ];

        protected $casts = [
            'subtotal' => 'float',
            'discount_amount' => 'float',
            'shipping_cost' => 'float',
            'total_amount' => 'float',
            'commission_amount' => 'float',
            'items' => 'collection',
            'tags' => 'collection',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
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

        public function customer(): BelongsTo
        {
            return $this->belongsTo(\App\Models\User::class, 'customer_id');
        }

        public function returns(): HasMany
        {
            return $this->hasMany(FashionReturn::class, 'order_id');
        }

        public function reviews(): HasMany
        {
            return $this->hasMany(FashionReview::class, 'order_id');
        }
}
