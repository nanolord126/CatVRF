<?php

declare(strict_types=1);

namespace Modules\GeoLogistics\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class DeliveryRoute extends Model
{
    use SoftDeletes;

    protected $table = 'delivery_routes';

    protected $fillable = [
        'tenant_id',
        'zone_id',
        'start_latitude',
        'start_longitude',
        'end_latitude',
        'end_longitude',
        'distance_km',
        'estimated_minutes',
        'status',
        'correlation_id',
    ];

    protected $casts = [
        'start_latitude' => 'decimal:8',
        'start_longitude' => 'decimal:8',
        'end_latitude' => 'decimal:8',
        'end_longitude' => 'decimal:8',
        'distance_km' => 'decimal:3',
        'estimated_minutes' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function zone()
    {
        return $this->belongsTo(DeliveryZone::class);
    }
}
