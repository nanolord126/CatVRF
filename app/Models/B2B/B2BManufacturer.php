<?php

namespace App\Models\B2B;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
use App\Traits\StrictTenantIsolation;
use App\Traits\HasAuditLog;
use App\Traits\HasCorrelationId;

class B2BManufacturer extends Model
{
    use StrictTenantIsolation;
    use BelongsToTenant;
    use HasAuditLog;
    use HasCorrelationId;

    protected $table = 'b2b_manufacturers';

    protected $fillable = [
        'name', 'is_platform_partner', 'slug', 'settings', 'tenant_id'
    ];

    protected $casts = [
        'settings' => 'array',
        'is_platform_partner' => 'boolean'
    ];

    public function products(): HasMany
    {
        return $this->hasMany(B2BProduct::class, 'manufacturer_id');
    }
}
