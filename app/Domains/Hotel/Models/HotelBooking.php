<?php

namespace App\Domains\Hotel\Models;

use App\Traits\Common\HasEcosystemFeatures;
use App\Traits\Common\HasEcosystemAuth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * HotelBooking Model - Бронирование отеля
 * 
 * Production ready model для управления бронированиями в домене Hotel.
 * Содержит информацию о бронировании, гостях, номере и статусе.
 */
class HotelBooking extends Model
{
    use HasEcosystemFeatures, HasEcosystemAuth;

    protected $table = 'hotel_bookings';
    protected $guarded = [];

    protected $casts = [
        'total_price' => 'decimal:2',
        'check_in' => 'datetime',
        'check_out' => 'datetime',
        'cancelled_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected $fillable = [
        'tenant_id',
        'hotel_id',
        'room_id',
        'guest_id',
        'check_in',
        'check_out',
        'total_price',
        'status',
        'special_requests',
        'correlation_id',
        'metadata',
    ];

    // ============================================
    // RELATIONS
    // ============================================

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'hotel_id');
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'guest_id');
    }

    // ============================================
    // BUSINESS LOGIC
    // ============================================

    public function isActive(): bool
    {
        return $this->status === 'confirmed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'confirmed']);
    }

    public function getNumberOfNights(): int
    {
        return $this->check_out->diffInDays($this->check_in);
    }
}
