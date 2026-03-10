<?php

namespace App\Models\CRM;

use App\Traits\StrictTenantIsolation;
use App\Traits\HasEcosystemTracing;

use Illuminate\Database\Eloquent\Model;
use Stancl\\Tenancy\\Database\\Concerns\\BelongsToTenant;

class Robot extends Model

{
    use BelongsToTenant;
    use StrictTenantIsolation;
    use HasEcosystemTracing;
    protected $fillable = [
        'name', 'trigger_type', 'trigger_config', 
        'action_type', 'action_config', 'is_active', 
        'tenant_id'
    ];

    protected $casts = [
        'trigger_config' => 'json',
        'action_config' => 'json'
    ];
}








