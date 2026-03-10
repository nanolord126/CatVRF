<?php

namespace App\Models;

use App\Traits\StrictTenantIsolation;
use App\Traits\HasEcosystemTracing;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasEcosystemTracing;

/**
 * Bulk order from a Tenant to a Manufacturer.
 */
class B2BBulkOrder extends Model
{
    use StrictTenantIsolation;
    use HasEcosystemTracing;
    use BelongsToTenant;
    use HasEcosystemTracing;

    protected $table = 'b2b_bulk_orders';

    protected $fillable = [
        'manufacturer_id',
        'tenant_id',
        'contract_id',
        'total_amount',
        'commission_amount',
        'status',
        'payment_status',
        'expected_delivery_at',
        'correlation_id'
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'expected_delivery_at' => 'datetime',
    ];

    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(B2BManufacturer::class, 'manufacturer_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(WholesaleContract::class, 'contract_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(B2BBulkOrderItem::class, 'order_id');
    }
}








