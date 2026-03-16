<?php

declare(strict_types=1);

namespace Modules\Delivery\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryOrder extends Model
{
    protected $fillable = [
        'order_number',
        'status',
        'delivery_date',
        'pickup_address',
        'delivery_address',
        'price',
        'distance_km',
    ];

    protected $casts = [
        'delivery_date' => 'date',
        'price' => 'decimal:2',
        'distance_km' => 'float',
    ];
}
