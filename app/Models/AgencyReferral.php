<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\StrictTenantIsolation;
use App\Traits\HasEcosystemTracing;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

final class AgencyReferral extends Model
{
    use StrictTenantIsolation;
    use HasEcosystemTracing;
    protected $fillable = [
        'name',
        'email',
        'company_name',
        'suggested_role',
        'status',
    ];
}










