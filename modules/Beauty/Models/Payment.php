<?php

namespace Modules\Beauty\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Beauty\Enums\PaymentStatus;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'beauty_payments';

    protected $fillable = [
        'booking_id',
        'tenant_id',
        'salon_id',
        'amount',
        'status',
        'payment_method',
        'tinkoff_payment_id',
        'salon_payout_amount',
        'platform_commission_amount',
        'commission_percent',
        'completed_at',
        'correlation_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'salon_payout_amount' => 'decimal:2',
        'platform_commission_amount' => 'decimal:2',
        'commission_percent' => 'decimal:2',
        'status' => PaymentStatus::class,
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    public function salon(): BelongsTo
    {
        return $this->belongsTo(BeautySalon::class, 'salon_id');
    }

    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', PaymentStatus::CONFIRMED);
    }

    public function scopePending($query)
    {
        return $query->where('status', PaymentStatus::PENDING);
    }

    public function markAsConfirmed(): self
    {
        $this->update([
            'status' => PaymentStatus::CONFIRMED,
            'completed_at' => now(),
        ]);
        return $this;
    }

    public function markAsFailed(): self
    {
        $this->update(['status' => PaymentStatus::FAILED]);
        return $this;
    }

    public function markAsRefunded(): self
    {
        $this->update(['status' => PaymentStatus::REFUNDED]);
        return $this;
    }

    public function isConfirmed(): bool
    {
        return $this->status === PaymentStatus::CONFIRMED;
    }

    public function isPending(): bool
    {
        return $this->status === PaymentStatus::PENDING;
    }
}
