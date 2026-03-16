<?php

declare(strict_types=1);

namespace Modules\GeoLogistics\Models;

use Illuminate\Database\Eloquent\Model;

final class DeliveryStatus extends Model
{
    public $timestamps = false;

    protected $table = 'delivery_statuses';

    protected $fillable = [
        'delivery_id',
        'status',
        'latitude',
        'longitude',
        'timestamp',
        'notes',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'timestamp' => 'datetime',
    ];
}
