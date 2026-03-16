<?php

declare(strict_types=1);

namespace Modules\GeoLogistics\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class DeliveryZone extends Model
{
    use SoftDeletes;

    protected $table = 'delivery_zones';

    protected $fillable = [
        'tenant_id',
        'name',
        'polygon_coordinates',
        'base_price',
        'delivery_time_minutes',
        'is_active',
        'correlation_id',
    ];

    protected $casts = [
        'polygon_coordinates' => 'json',
        'base_price' => 'decimal:2',
        'delivery_time_minutes' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function routes()
    {
        return $this->hasMany(DeliveryRoute::class, 'zone_id');
    }
}
