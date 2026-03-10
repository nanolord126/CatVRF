<?php

namespace App\Models;

use App\Traits\StrictTenantIsolation;
use App\Traits\HasEcosystemTracing;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class OfflineSync extends Model
{
    use StrictTenantIsolation;
    use HasEcosystemTracing;
    //
}









