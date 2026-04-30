<?php

declare(strict_types=1);

namespace App\Domains\Fashion\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

final class FashionReturn extends Model
{
    use TenantScoped;

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

    public function order(): BelongsTo
    {
        return $this->belongsTo(FashionOrder::class, 'order_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    protected static function booted(): void
    {
        self::addGlobalScope('tenant_id', function ($query) {
            if (tenant()->id) {
                $query->where('tenant_id', tenant()->id);
            }
        });
    }
}
