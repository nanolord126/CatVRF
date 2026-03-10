<?php

namespace App\Models\CRM;

use App\Traits\StrictTenantIsolation;
use App\Traits\HasEcosystemTracing;

use Illuminate\Database\Eloquent\Model;
use Stancl\\Tenancy\\Database\\Concerns\\BelongsToTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Stage extends Model

{
    use BelongsToTenant;
    use StrictTenantIsolation;
    use HasEcosystemTracing;
    protected $fillable = ['pipeline_id', 'name', 'color', 'sort_order', 'is_win', 'is_loss'];

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(Pipeline::class);
    }
}








