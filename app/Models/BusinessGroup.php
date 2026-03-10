<?php

namespace App\Models;

use App\Traits\StrictTenantIsolation;
use App\Traits\HasEcosystemTracing;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class BusinessGroup extends Model
{
    use StrictTenantIsolation;
    use HasEcosystemTracing;
    protected $connection = 'central';
    protected $fillable = ['name', 'inn', 'owner_id', 'correlation_id'];
    public function tenants() { return $this->hasMany(Tenant::class); }
}









