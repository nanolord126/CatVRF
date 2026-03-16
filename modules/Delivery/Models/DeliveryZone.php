<?php

declare(strict_types=1);

namespace Modules\Delivery\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryZone extends Model
{
    protected $fillable = [
        'name',
        'latitude',
        'longitude',
        'radius_km',
        'delivery_fee',
        'status',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'radius_km' => 'float',
        'delivery_fee' => 'decimal:2',
    ];
}
