<?php

namespace App\Domains\RealEstate\Models;

use App\Traits\HasAuditLog;
use App\Traits\HasEcosystemFeatures;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Property extends Model
{
    use HasAuditLog, HasEcosystemFeatures;

    protected $fillable = [
        'tenant_id', 'owner_id', 'title', 'description',
        'address', 'latitude', 'longitude', 'bedrooms', 'bathrooms',
        'area_sqm', 'price_per_night', 'status'
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'owner_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }
}
