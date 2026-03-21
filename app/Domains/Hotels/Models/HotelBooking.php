<?php

declare(strict_types=1);

namespace App\Domains\Hotels\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class HotelBooking extends Model
{
    use SoftDeletes, TenantScoped;

    protected $table = 'hotel_bookings';

    protected $fillable = [
        'hotel_id',
        'user_id',
        'correlation_id',
        'booking_number',
        'room_type_id',
        'guest_name',
        'guest_email',
        'guest_phone',
        'check_in_date',
        'check_out_date',
        'number_of_guests',
        'number_of_nights',
        'room_price_per_night',
        'total_nights_cost',
        'deposit_amount',
        'total_cost',
        'status',
        'payment_status',
        'special_requests',
        'tags',
        'meta',
    ];

    protected $casts = [
        'check_in_date' => 'date',
        'check_out_date' => 'date',
        'number_of_guests' => 'integer',
        'number_of_nights' => 'integer',
        'room_price_per_night' => 'integer',
        'total_nights_cost' => 'integer',
        'deposit_amount' => 'integer',
        'total_cost' => 'integer',
        'tags' => 'json',
        'meta' => 'json',
    ];

    protected $hidden = ['correlation_id', 'meta'];

    /**
     * Отель
     */
    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class, 'hotel_id', 'id');
    }

    /**
     * Пользователь
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id', 'id');
    }

    /**
     * Тип комнаты
     */
    public function roomType(): BelongsTo
    {
        return $this->belongsTo(HotelRoomType::class, 'room_type_id', 'id');
    }

    /**
     * Проверить, подтверждена ли бронь
     */
    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    /**
     * Проверить, завершена ли бронь
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Проверить, отменена ли бронь
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Получить кол-во ночей
     */
    public function calculateNights(): int
    {
        return $this->check_out_date->diffInDays($this->check_in_date);
    }

    protected static function booted(): void
    {
        parent::booted();
        static::addGlobalScope("tenant_id", function ($query) {
            if (function_exists("tenant") && tenant("id")) {
                $query->where("tenant_id", tenant("id"));
            }
        });
    }
}
