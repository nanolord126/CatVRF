<?php

namespace App\Models\CRM;

use App\Traits\StrictTenantIsolation;
use App\Traits\HasEcosystemTracing;

use Illuminate\Database\Eloquent\Model;
use Stancl\\Tenancy\\Database\\Concerns\\BelongsToTenant;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasCorrelationId;
use App\Traits\HasAuditLog;

class Pipeline extends Model

{
    use BelongsToTenant;
    use StrictTenantIsolation;
    use HasEcosystemTracing;
    use HasCorrelationId, HasAuditLog;

    protected $fillable = ['name', 'is_default', 'settings', 'tenant_id'];
    protected $casts = ['settings' => 'json'];

    public function stages(): HasMany
    {
        return $this->hasMany(Stage::class)->orderBy('sort_order');
    }

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class);
    }
}








