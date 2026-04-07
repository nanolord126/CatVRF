<?php

declare(strict_types=1);

namespace App\Domains\Hotels\HotelManagement\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * HotelBooking — модель бронирования гостиницы CatVRF 2026.
 *
 * Хранит данные о бронировании номеров: гость, даты,
 * стоимость в копейках, статус оплаты.
 *
 * @package CatVRF
 * @version 2026.1
 * @see https://catvrf.ru/docs/hotelbooking
 */
final class HotelBooking extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $table = 'hotel_bookings';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'hotel_id',
        'guest_id',
        'correlation_id',
        'status',
        'total_kopecks',
        'payout_kopecks',
        'payment_status',
        'room_type',
        'check_in',
        'check_out',
        'nights_count',
        'tags',
    ];

    protected $casts = [
        'total_kopecks' => 'integer',
        'payout_kopecks' => 'integer',
        'check_in' => 'datetime',
        'check_out' => 'datetime',
        'nights_count' => 'integer',
        'tags' => 'json',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query): void {
            $query->where('hotel_bookings.tenant_id', tenant()->id);
        });

        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class, 'hotel_id');
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'guest_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class, 'tenant_id');
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

    /**
     * Отладочные данные для логирования.
     *
     * @return array<string, mixed>
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'id' => $this->id,
            'uuid' => $this->uuid,
            'tenant_id' => $this->tenant_id,
            'hotel_id' => $this->hotel_id,
            'status' => $this->status,
            'total_kopecks' => $this->total_kopecks,
            'nights_count' => $this->nights_count,
            'timestamp' => Carbon::now()->toIso8601String(),
        ];
    }
}
