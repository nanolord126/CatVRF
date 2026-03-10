<?php

namespace App\Models\CRM;

use App\Traits\StrictTenantIsolation;
use App\Traits\HasEcosystemTracing;

use Illuminate\Database\Eloquent\Model;
use Stancl\\Tenancy\\Database\\Concerns\\BelongsToTenant;
use App\Traits\HasCorrelationId;
use App\Traits\HasAuditLog;

class Project extends Model

{
    use BelongsToTenant;
    use StrictTenantIsolation;
    use HasEcosystemTracing;
    use HasCorrelationId, HasAuditLog;

    protected $fillable = ['name', 'description', 'status', 'deadline', 'tenant_id'];

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }
}








