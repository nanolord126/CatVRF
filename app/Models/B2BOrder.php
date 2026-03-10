<?php

namespace App\Models;

use App\Traits\StrictTenantIsolation;
use App\Traits\HasEcosystemTracing;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * B2B Order - Financial record of a booking/appointment for a corporate partner
 */
class B2BOrder extends Model
{
    use StrictTenantIsolation;
    use HasEcosystemTracing;
    protected $table = 'b2b_orders';

    protected $fillable = [
        'partner_id',
        'contract_id',
        'origin_type',
        'origin_id',
        'amount',
        'status',
        'correlation_id',
    ];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(B2BPartner::class, 'partner_id');
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(B2BContract::class, 'contract_id');
    }

    public function origin(): MorphTo
    {
        return $this->morphTo();
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(B2BInvoice::class, 'order_id');
    }
}









