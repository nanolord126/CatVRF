<?php declare(strict_types=1);

namespace App\Domains\Education\Bloggers\Models;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class StreamOrder extends Model
{
    use HasFactory;

    use HasFactory, SoftDeletes;

        protected $table = 'stream_orders';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'stream_id',
            'user_id',
            'business_group_id',
            'order_reference',
            'stream_product_id',
            'status',
            'subtotal',
            'discount',
            'shipping_cost',
            'total',
            'payment_method',
            'payment_id',
            'idempotency_key',
            'tags',
            'correlation_id',
            'paid_at',
            'delivered_at',
        ];

        protected $casts = [
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_at' => 'datetime',
            'delivered_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
            'tags' => 'json',
        ];

        protected $hidden = ['idempotency_key', 'correlation_id'];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', function ($query) {
                $query->where('stream_orders.tenant_id', tenant()->id);
            });
        }

        public function stream(): BelongsTo
        {
            return $this->belongsTo(Stream::class, 'stream_id');
        }

        public function user(): BelongsTo
        {
            return $this->belongsTo(\App\Models\User::class, 'user_id');
        }

        public function product(): BelongsTo
        {
            return $this->belongsTo(StreamProduct::class, 'stream_product_id');
        }

        public function isPaid(): bool
        {
            return $this->status === 'paid';
        }

        public function isDelivered(): bool
        {
            return $this->status === 'delivered';
        }

        public function isCancelled(): bool
        {
            return $this->status === 'cancelled';
        }
}
