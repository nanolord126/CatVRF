<?php

namespace App\Models;

use App\Traits\StrictTenantIsolation;
use App\Traits\HasEcosystemTracing;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasEcosystemTracing;

/**
 * Wholesale Contract between a Manufacturer and a Tenant.
 */
class WholesaleContract extends Model
{
    use StrictTenantIsolation;
    use HasEcosystemTracing;
    use BelongsToTenant;
    use HasEcosystemTracing;

    protected $table = 'b2b_wholesale_contracts';

    protected $fillable = [
        'manufacturer_id',
        'tenant_id',
        'contract_number',
        'signed_at',
        'expires_at',
        'special_discount_percent',
        'credit_limit',
        'deferred_payment_days',
        'status',
        'correlation_id'
    ];

    protected $casts = [
        'signed_at' => 'date',
        'expires_at' => 'date',
        'special_discount_percent' => 'decimal:2',
        'credit_limit' => 'decimal:2',
    ];

    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(B2BManufacturer::class, 'manufacturer_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}








