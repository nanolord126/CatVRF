<?php

namespace App\Domains\Taxi\Models;

use App\Traits\Common\HasEcosystemFeatures;
use Illuminate\Database\Eloquent\Model;

class TaxiSurgeZone extends Model
{
    use HasEcosystemFeatures;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'multiplier' => 'float',
        'radius' => 'float',
        'lat' => 'float',
        'lng' => 'float',
        'expires_at' => 'datetime',
        'meta' => 'array',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('expires_at', '>', now());
    }
}
