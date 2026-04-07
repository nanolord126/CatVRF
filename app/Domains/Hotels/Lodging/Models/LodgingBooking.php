<?php

declare(strict_types=1);

namespace App\Domains\Hotels\Lodging\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * LodgingBooking — бронирование в гостевом доме CatVRF 2026.
 *
 * Хранит данные о бронировании: клиент, даты заезда/выезда,
 * стоимость в копейках, статус оплаты.
 *
 * @package CatVRF
 * @version 2026.1
 * @see https://catvrf.ru/docs/lodgingbooking
 */
final class LodgingBooking extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $table = 'lodging_bookings';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'lodge_id',
        'client_id',
        'correlation_id',
        'status',
        'total_kopecks',
        'payout_kopecks',
        'payment_status',
        'check_in',
        'check_out',
        'tags',
    ];

    protected $casts = [
        'total_kopecks' => 'integer',
        'payout_kopecks' => 'integer',
        'check_in' => 'date',
        'check_out' => 'date',
        'tags' => 'json',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query): void {
            $query->where('lodging_bookings.tenant_id', tenant()->id);
        });

        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }

    public function lodge(): BelongsTo
    {
        return $this->belongsTo(Lodge::class, 'lodge_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'client_id');
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
            '%s[id=%s, lodge=%s, status=%s]',
            static::class,
            $this->id ?? 'new',
            $this->lodge_id ?? 'N/A',
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
            'lodge_id' => $this->lodge_id,
            'status' => $this->status,
            'total_kopecks' => $this->total_kopecks,
            'timestamp' => Carbon::now()->toIso8601String(),
        ];
    }
}
