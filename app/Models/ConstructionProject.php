<?php
namespace App\Models;

use App\Traits\StrictTenantIsolation;
use App\Traits\HasEcosystemTracing;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class ConstructionProject extends Model
{
    use StrictTenantIsolation;
    use HasEcosystemTracing;
    protected $fillable = ['name', 'tenant_id', 'status', 'budget'];
    protected static function booted() {
        static::creating(fn ($m) => $m->tenant_id = tenant()->id ?? 'central');
    }
}









