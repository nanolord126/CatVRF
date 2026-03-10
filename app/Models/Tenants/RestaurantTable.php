<?php

namespace App\Models\Tenants;

use App\Traits\StrictTenantIsolation;
use App\Traits\HasEcosystemTracing;

use Illuminate\Database\Eloquent\Model;
use Stancl\\Tenancy\\Database\\Concerns\\BelongsToTenant;

class RestaurantTable extends Model

{
    use BelongsToTenant;
    use StrictTenantIsolation;
    use HasEcosystemTracing;
    protected $fillable = ['number', 'capacity', 'status'];
}








