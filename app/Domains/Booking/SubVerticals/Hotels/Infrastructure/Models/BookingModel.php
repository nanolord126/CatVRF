<?php

declare(strict_types=1);

namespace Modules\Hotels\Infrastructure\Models;

use App\Models\BaseDomainModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class BookingModel extends BaseDomainModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'hotels_bookings';

    protected $fillable = [
        'tenant_id',
        'venue_id',
        'guest_id',
        'user_id',
        'uuid',
        'confirmation_code',
        'status',
        'source',
        'external_reference',
        'check_in_date',
        'check_out_date',
        'adults',
        'children',
        'infants',
        'total_amount',
        'paid_amount',
        'deposit_amount',
        'currency',
        'payment_status',
        'special_requests',
        'room_preferences',
        'early_checkin_requested',
        'late_checkout_requested',
        'actual_check_in',
        'actual_check_out',
        'created_by',
        'modified_by',
        'cancellation_reason',
        'cancelled_at',
        'notes',
        'correlation_id',
    ];

    protected $casts = [
        'check_in_date' => 'datetime',
        'check_out_date' => 'datetime',
        'adults' => 'integer',
        'children' => 'integer',
        'infants' => 'integer',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'special_requests' => 'array',
        'room_preferences' => 'array',
        'early_checkin_requested' => 'boolean',
        'late_checkout_requested' => 'boolean',
        'actual_check_in' => 'datetime',
        'actual_check_out' => 'datetime',
        'cancelled_at' => 'datetime',
        'notes' => 'array',
    ];

    public function venue(): BelongsTo
    {
        return $this->belongsTo(VenueModel::class, 'venue_id');
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(GuestModel::class, 'guest_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BookingItemModel::class, 'booking_id');
    }

    public function externalReferences(): HasMany
    {
        return $this->hasMany(ExternalBookingReferenceModel::class, 'booking_id');
    }

    public function canCheckIn(): bool
    {
        return in_array($this->status, ['prepaid', 'paid']) && $this->check_in_date->isPast();
    }

    public function canCancel(): bool
    {
        return in_array($this->status, ['pending', 'prepaid']) && $this->check_in_date->isFuture();
    }
}
