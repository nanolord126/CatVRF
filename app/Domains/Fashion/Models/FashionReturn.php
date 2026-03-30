<?php declare(strict_types=1);

namespace App\Domains\Fashion\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FashionReturn extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use SoftDeletes;

        protected $table = 'fashion_returns';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'order_id',
            'customer_id',
            'return_number',
            'return_amount',
            'reason',
            'reason_details',
            'items',
            'status',
            'requested_at',
            'approved_at',
            'shipped_at',
            'received_at',
            'refunded_at',
            'tracking_number',
            'transaction_id',
            'restocking_fee_percent',
            'refund_amount',
            'correlation_id',
        ];

        protected $casts = [
            'return_amount' => 'float',
            'refund_amount' => 'float',
            'items' => 'collection',
            'requested_at' => 'datetime',
            'approved_at' => 'datetime',
            'shipped_at' => 'datetime',
            'received_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant_id', function ($query) {
                if (tenant('id')) {
                    $query->where('tenant_id', tenant('id'));
                }
            });
        }

        public function order(): BelongsTo
        {
            return $this->belongsTo(FashionOrder::class, 'order_id');
        }

        public function customer(): BelongsTo
        {
            return $this->belongsTo(\App\Models\User::class, 'customer_id');
        }
}
