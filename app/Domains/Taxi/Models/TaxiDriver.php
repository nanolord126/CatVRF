<?php

namespace App\Domains\Taxi\Models;

use App\Traits\Common\HasEcosystemFeatures;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxiDriver extends Model
{
    use HasEcosystemFeatures;

    protected $guarded = [];

    protected $casts = [
        'last_location' => 'array',
        'rating' => 'float',
        'last_online_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(TaxiVehicle::class, 'current_vehicle_id');
    }

    public function rides(): HasMany
    {
        return $this->hasMany(TaxiRide::class);
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(TaxiShift::class, 'driver_id');
    }
}
