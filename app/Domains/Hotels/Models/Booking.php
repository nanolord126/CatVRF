<?php

declare(strict_types=1);

namespace App\Domains\Hotels\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Booking — модель бронирования отеля CatVRF 2026.
 *
 * Содержит данные бронирования: номер, гость, даты заезда/выезда,
 * стоимость, статус оплаты, B2B-контракт.
 *
 * @package CatVRF
 * @version 2026.1
 * @see https://catvrf.ru/docs/booking
 */
final class Booking extends Model
{

    protected $table = 'hotel_bookings';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'hotel_id',
        'room_id',
        'user_id',
        'check_in',
        'check_out',
        'status',
        'total_price',
        'currency',
        'payment_status',
        'payout_at',
        'is_b2b',
        'contract_id',
        'metadata',
        'correlation_id',
    ];

    protected $casts = [
        'check_in' => 'datetime',
        'check_out' => 'datetime',
        'total_price' => 'integer',
        'is_b2b' => 'boolean',
        'metadata' => 'json',
        'payout_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });

        static::addGlobalScope('tenant', function ($builder): void {
            $builder->where('hotel_bookings.tenant_id', tenant()->id);
        });
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class, 'hotel_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class, 'tenant_id');
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(B2BContract::class, 'contract_id');
    }

    /**
     * Возвращает true, если бронирование оплачено.
     */
    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    /**
     * Рассчитать количество ночей.
     */
    public function nights(): int
    {
        if ($this->check_in === null || $this->check_out === null) {
            return 0;
        }

        return (int) $this->check_in->diffInDays($this->check_out);
    }

    /**
     * Получить строковое представление модели.
     */
    public function __toString(): string
    {
        return sprintf(
            '%s[id=%s, hotel=%s, status=%s]',
            static::class,
            $this->id ?? 'new',
            $this->hotel_id ?? 'N/A',
            $this->status ?? 'unknown',
        );
    }
}
