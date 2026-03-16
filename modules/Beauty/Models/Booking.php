<?php

namespace Modules\Beauty\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Beauty\Enums\BookingStatus;

class Booking extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'beauty_bookings';

    protected $fillable = [
        'service_id',
        'customer_id',
        'tenant_id',
        'salon_id',
        'scheduled_at',
        'status',
        'notes',
        'correlation_id',
    ];

    protected $casts = [
        'status' => BookingStatus::class,
        'scheduled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function salon(): BelongsTo
    {
        return $this->belongsTo(BeautySalon::class, 'salon_id');
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class, 'booking_id');
    }

    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('scheduled_at', '>=', now())
                     ->whereIn('status', [BookingStatus::CONFIRMED, BookingStatus::PENDING]);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', BookingStatus::COMPLETED);
    }

    public function canBePaid(): bool
    {
        return $this->status === BookingStatus::PENDING || $this->status === BookingStatus::UNPAID;
    }

    public function markAsConfirmed(): self
    {
        $this->update(['status' => BookingStatus::CONFIRMED]);
        return $this;
    }

    public function markAsCompleted(): self
    {
        $this->update(['status' => BookingStatus::COMPLETED]);
        return $this;
    }

    public function markAsCancelled(): self
    {
        $this->update(['status' => BookingStatus::CANCELLED]);
        return $this;
    }
}
