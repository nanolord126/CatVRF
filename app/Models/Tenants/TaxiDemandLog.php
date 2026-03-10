<?php

namespace App\Models\Tenants;

use App\Traits\StrictTenantIsolation;
use App\Traits\HasEcosystemTracing;

use Illuminate\Database\Eloquent\Model;
use Stancl\\Tenancy\\Database\\Concerns\\BelongsToTenant;

class TaxiDemandLog extends Model

{
    use BelongsToTenant;
    use StrictTenantIsolation;
    use HasEcosystemTracing;
    protected $table = 'taxi_demand_logs';
    protected $fillable = ['location_geo', 'requested_at'];
    protected $casts = ['location_geo' => 'array', 'requested_at' => 'datetime'];
}








