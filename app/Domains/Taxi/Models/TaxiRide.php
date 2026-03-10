<?php

namespace App\Domains\Taxi\Models;

use App\Traits\Common\HasEcosystemFeatures;
use App\Traits\Common\HasEcosystemAuth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxiRide extends Model
{
    use HasEcosystemFeatures, HasEcosystemAuth;

    protected $guarded = [];

    protected $casts = [
        'pickup_coords' => 'array',
        'destination_coords' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'estimated_price' => 'decimal:2',
        'final_price' => 'decimal:2',
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(TaxiDriver::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(TaxiVehicle::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(TaxiRideStatusLog::class);
    }
}
