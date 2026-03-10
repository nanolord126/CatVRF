<?php

namespace Modules\Delivery\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Common\HasEcosystemFeatures;

class DeliveryZone extends Model
{
    use HasEcosystemFeatures;

    protected $fillable = ['name', 'radius_km', 'delivery_fee', 'is_active', 'geo_json'];

    protected $casts = [
        'is_active' => 'boolean',
        'geo_json' => 'array',
        'radius_km' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
    ];
}

class DeliveryOrder extends Model
{
    use HasEcosystemFeatures;

    protected $fillable = [
        'customer_id', 'delivery_zone_id', 'status', 'address', 
        'latitude', 'longitude', 'delivery_fee', 'correlation_id'
    ];

    public function zone()
    {
        return $this->belongsTo(DeliveryZone::class, 'delivery_zone_id');
    }
}
