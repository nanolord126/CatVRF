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

class Deal extends Model

{
    use BelongsToTenant;
    use StrictTenantIsolation;
    use HasEcosystemTracing;
    use HasCorrelationId, HasAuditLog;

    protected $fillable = [
        'name', 'pipeline_id', 'stage_id', 'contact_id', 
        'company_id', 'user_id', 'amount', 'currency', 
        'closed_at', 'tenant_id'
    ];

    public function stage(): BelongsTo
    {
        return $this->belongsTo(Stage::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}








