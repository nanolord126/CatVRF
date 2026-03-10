<?php
namespace App\Models;

use App\Traits\StrictTenantIsolation;
use App\Traits\HasEcosystemTracing;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class PromoCampaign extends Model
{
    use StrictTenantIsolation;
    use HasEcosystemTracing;
    protected $fillable = ['name', 'tenant_id', 'type', 'rules', 'is_active'];
    protected $casts = ['rules' => 'array'];
    protected static function booted() {
        static::creating(fn ($m) => $m->tenant_id = tenant()->id ?? 'central');
    }
}









