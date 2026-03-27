<?php

declare(strict_types=1);

namespace App\Domains\Luxury\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * VIPBooking
 *
 * Layer 1: Model Layer
 * Описывает бронирование товара (для примерки/покупки) или услуги.
 *
 * @version 1.0.0
 * @author CatVRF
 */
final class VIPBooking extends Model
{
    use SoftDeletes;

    protected $table = 'luxury_vip_bookings';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'client_id',
        'bookable_type',
        'bookable_id',
        'status', // pending, confirmed, fulfilled, cancelled
        'booking_at',
        'duration_minutes',
        'total_price_kopecks',
        'deposit_kopecks',
        'payment_status', // unpaid, deposited, paid, refunded
        'concierge_id',
        'notes',
        'correlation_id',
    ];

    protected $casts = [
        'booking_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->uuid = (string) Str::uuid();
            if (empty($model->tenant_id) && function_exists('tenant') && tenant()) {
                $model->tenant_id = tenant()->id;
            }
        });

        static::addGlobalScope('tenant', function (Builder $builder) {
            if (function_exists('tenant') && tenant()) {
                $builder->where('luxury_vip_bookings.tenant_id', tenant()->id);
            }
        });
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(LuxuryClient::class, 'client_id');
    }

    public function bookable(): MorphTo
    {
        return $this->morphTo();
    }
}
