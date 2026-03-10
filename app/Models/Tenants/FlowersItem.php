<?php

namespace App\Models\Tenants;

use App\Traits\StrictTenantIsolation;
use App\Traits\HasEcosystemTracing;

use Illuminate\Database\Eloquent\Model;
use Stancl\\Tenancy\\Database\\Concerns\\BelongsToTenant;

class FlowersItem extends Model

{
    use BelongsToTenant;
    use StrictTenantIsolation;
    use HasEcosystemTracing;
    protected $fillable = ['name', 'description', 'price', 'composition', 'is_available', 'correlation_id'];
    protected $casts = [
        'composition' => 'array',
        'is_available' => 'boolean',
        'price' => 'decimal:2',
    ];
}








