<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Verticals\Hotels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\CatCRM\Domain\Entities\Deal;
use Modules\CatCRM\Domain\Entities\Customer;

/**
 * Hotel Booking — Бронирование в вертикали Гостиницы
 * 
 * Расширяет базовую Deal модель специфичными полями для гостиниц.
 */
final class HotelBooking extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'deal_id',
        'customer_id',
        'check_in_date',
        'check_out_date',
        'room_type',
        'room_number',
        'guests_count',
        'guests_names',
        'special_requests',
        'breakfast_included',
        'price_per_night',
        'total_price',
        'deposit_amount',
        'deposit_paid',
        'payment_status',
        'booking_status',
        'check_in_time',
        'check_out_time',
        'actual_check_in',
        'actual_check_out',
        'key_card_number',
        'metadata',
        'correlation_id',
        'uuid',
    ];

    protected $casts = [
        'check_in_date' => 'datetime',
        'check_out_date' => 'datetime',
        'actual_check_in' => 'datetime',
        'actual_check_out' => 'datetime',
        'guests_count' => 'integer',
        'breakfast_included' => 'boolean',
        'deposit_paid' => 'boolean',
        'price_per_night' => 'integer',
        'total_price' => 'integer',
        'deposit_amount' => 'integer',
        'guests_names' => 'json',
        'metadata' => 'json',
    ];

    protected $table = 'crm_hotel_bookings';

    // ========================
    // RELATIONSHIPS
    // ========================

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    // ========================
    // SCOPES
    // ========================

    public function scopeByDeal($query, int $dealId)
    {
        return $query->where('deal_id', $dealId);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('booking_status', ['confirmed', 'checked_in']);
    }

    public function scopeCheckedIn($query)
    {
        return $query->where('booking_status', 'checked_in');
    }

    public function scopeCheckedOut($query)
    {
        return $query->where('booking_status', 'checked_out');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('check_in_date', '>=', now())
            ->whereIn('booking_status', ['confirmed', 'pending']);
    }

    // ========================
    // METHODS
    // ========================

    public function checkIn(string $keyCardNumber): bool
    {
        $this->booking_status = 'checked_in';
        $this->actual_check_in = now();
        $this->key_card_number = $keyCardNumber;

        return $this->save();
    }

    public function checkOut(): bool
    {
        $this->booking_status = 'checked_out';
        $this->actual_check_out = now();

        return $this->save();
    }

    public function getNightsCount(): int
    {
        if ($this->check_in_date === null || $this->check_out_date === null) {
            return 0;
        }

        return $this->check_in_date->diffInDays($this->check_out_date);
    }

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function ($model) {
            $model->uuid ??= \Illuminate\Support\Str::uuid()->toString();
            
            // Автоматический расчет total_price
            if ($model->total_price === 0 && $model->price_per_night > 0) {
                $model->total_price = $model->price_per_night * max(1, $model->getNightsCount());
            }
        });
    }
}
