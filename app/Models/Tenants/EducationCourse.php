<?php

namespace App\Models\Tenants;

use App\Traits\StrictTenantIsolation;
use App\Traits\HasEcosystemTracing;

use Illuminate\Database\Eloquent\Model;
use Stancl\\Tenancy\\Database\\Concerns\\BelongsToTenant;

class EducationCourse extends Model

{
    use BelongsToTenant;
    use StrictTenantIsolation;
    use HasEcosystemTracing;
    protected $fillable = ['title', 'type', 'price', 'duration_minutes'];
    protected $casts = ['price' => 'decimal:2'];
}








