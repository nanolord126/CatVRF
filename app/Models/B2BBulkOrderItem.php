<?php

namespace App\Models;

use App\Traits\StrictTenantIsolation;
use App\Traits\HasEcosystemTracing;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasEcosystemTracing;

/**
 * Item in a B2B Bulk Order.
 */
class B2BBulkOrderItem extends Model
{
    use StrictTenantIsolation;
    use HasEcosystemTracing;
    use BelongsToTenant;
    use HasEcosystemTracing;

    protected $table = 'b2b_bulk_order_items';

    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'unit_price',
        'subtotal',
        'correlation_id'
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(B2BBulkOrder::class, 'order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(B2BProduct::class, 'product_id');
    }
}








