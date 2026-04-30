<?php

declare(strict_types=1);

namespace App\Domains\Leisure\SubVerticals\Flowers\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class B2BFlowerOrder extends Model
{
    use HasFactory;
    use SoftDeletes;
    use TenantScoped;

    protected $table = 'b2b_flower_orders';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'tenant_id',
        'storefront_id',
        'shop_id',
        'order_number',
        'subtotal',
        'bulk_discount',
        'commission_amount',
        'total_amount',
        'delivery_address',
        'delivery_location',
        'delivery_date',
        'status',
        'payment_status',
        'correlation_id',
    ];

    protected $casts = [
        'delivery_location' => 'json',
        'delivery_date' => 'date',
        'subtotal' => 'decimal:2',
        'bulk_discount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function storefront(): BelongsTo
    {
        return $this->belongsTo(B2BFlowerStorefront::class);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(FlowerShop::class);
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
