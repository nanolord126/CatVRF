<?php

namespace App\Models;

use App\Traits\StrictTenantIsolation;
use App\Traits\HasEcosystemTracing;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Corporate Invoice - Bill generated for B2B Order
 */
class B2BInvoice extends Model
{
    use StrictTenantIsolation;
    use HasEcosystemTracing;
    protected $table = 'b2b_invoices';

    protected $fillable = [
        'order_id',
        'invoice_number',
        'amount',
        'due_date',
        'paid_at',
        'status',
        'payment_link',
        'correlation_id',
    ];

    protected $casts = [
        'due_date' => 'date',
        'paid_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(B2BOrder::class, 'order_id');
    }

    public function markAsPaid(): void
    {
        $this->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $this->order->update(['status' => 'paid']);
        
        // Logical connection: increase partner balance if needed or just record payment.
        // In "Canon", we should trigger record in audit log.
    }
}









