<?php

namespace App\Models;

use App\Traits\StrictTenantIsolation;
use App\Traits\HasEcosystemTracing;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Clinic extends Model
{
    use StrictTenantIsolation;
    use HasEcosystemTracing;
    protected $fillable = [
        'name',
        'type', // human, vet
        'address',
        'phone',
        'email',
        'geo_lat',
        'geo_lng',
        'correlation_id',
    ];

    public function doctors(): HasMany
    {
        return $this->hasMany(DoctorProfile::class);
    }
}









