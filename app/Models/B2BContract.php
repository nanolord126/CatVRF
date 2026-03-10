<?php

namespace App\Models;

use App\Traits\StrictTenantIsolation;
use App\Traits\HasEcosystemTracing;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * B2B Contract - Terms of collaboration (discount, credit limit, etc.)
 */
class B2BContract extends Model
{
    use StrictTenantIsolation;
    use HasEcosystemTracing;
    protected $table = 'b2b_contracts';

    protected $fillable = [
        'partner_id',
        'contract_number',
        'start_date',
        'end_date',
        'discount_percent',
        'credit_limit',
        'payment_terms_days',
        'status',
        'correlation_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'discount_percent' => 'decimal:2',
        'credit_limit' => 'decimal:2',
    ];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(B2BPartner::class, 'partner_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && 
               $this->start_date <= now() && 
               ($this->end_date === null || $this->end_date >= now());
    }
}









