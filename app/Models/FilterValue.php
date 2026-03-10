<?php

namespace App\Models;

use App\Traits\StrictTenantIsolation;
use App\Traits\HasEcosystemTracing;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FilterValue extends Model
{
    use StrictTenantIsolation;
    use HasEcosystemTracing;
    protected $fillable = ['filter_id', 'value', 'label'];

    public function filter(): BelongsTo { return $this->belongsTo(Filter::class); }
}









