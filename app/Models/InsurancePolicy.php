<?php
namespace App\Models;

use App\Traits\StrictTenantIsolation;
use App\Traits\HasEcosystemTracing;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class InsurancePolicy extends Model
{
    use StrictTenantIsolation;
    use HasEcosystemTracing;
    protected $fillable = ['number', 'tenant_id', 'type', 'expires_at', 'premium_amount'];
    protected static function booted() {
        static::creating(fn ($m) => $m->tenant_id = tenant()->id ?? 'central');
    }
}









