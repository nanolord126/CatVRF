<?php

namespace App\Models\CRM;

use App\Traits\StrictTenantIsolation;
use App\Traits\HasEcosystemTracing;

use Illuminate\Database\Eloquent\Model;
use Stancl\\Tenancy\\Database\\Concerns\\BelongsToTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasCorrelationId;
use App\Traits\HasAuditLog;
use App\Models\User;

class Task extends Model

{
    use BelongsToTenant;
    use StrictTenantIsolation;
    use HasEcosystemTracing;
    use HasCorrelationId, HasAuditLog;

    protected $fillable = [
        'title', 'description', 'status', 'priority',
        'creator_id', 'responsible_id', 'project_id',
        'due_at', 'remind_at', 'completed_at', 'tenant_id'
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_id');
    }
}








