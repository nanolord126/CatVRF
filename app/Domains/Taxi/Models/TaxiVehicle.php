<?php

namespace App\Domains\Taxi\Models;

use App\Traits\Common\HasEcosystemFeatures;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaxiVehicle extends Model
{
    use HasEcosystemFeatures;

    protected $guarded = [];

    protected $casts = [
        'features' => 'array',
        'is_active' => 'boolean',
        'last_inspection_at' => 'datetime',
        'mileage_km' => 'float',
    ];

    public function drivers(): HasMany
    {
        return $this->hasMany(TaxiDriver::class, 'current_vehicle_id');
    }

    public function rides(): HasMany
    {
        return $this->hasMany(TaxiRide::class, 'vehicle_id');
    }
}
