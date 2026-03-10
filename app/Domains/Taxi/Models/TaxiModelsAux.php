<?php

namespace App\Domains\Taxi\Models;

use App\Traits\Common\HasEcosystemFeatures;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxiShift extends Model
{
    use HasEcosystemFeatures;

    protected $guarded = [];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'total_earnings' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(TaxiDriver::class);
    }
}

class TaxiRideStatusLog extends Model
{
    protected $guarded = [];

    protected $casts = [
        'meta' => 'array',
    ];

    public function ride(): BelongsTo
    {
        return $this->belongsTo(TaxiRide::class);
    }
}
