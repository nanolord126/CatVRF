<?php

declare(strict_types=1);

namespace App\Domains\Luxury\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class VIPBooking extends Model
{
    use TenantScoped;

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

    public function client(): BelongsTo
    {
        return $this->belongsTo(LuxuryClient::class, 'client_id');
    }

    public function bookable(): MorphTo
    {
        return $this->morphTo();
    }

    protected static function booted_disabled(): void
    {
        self::creating(function (self $model) {
            $model->uuid = (string) Str::uuid();
            if (empty($model->tenant_id) && function_exists('tenant') && tenant()) {
                $model->tenant_id = tenant()->id;
            }
        });

        self::addGlobalScope('tenant', function (Builder $builder) {
            if (function_exists('tenant') && tenant()) {
                $builder->where('luxury_vip_bookings.tenant_id', tenant()->id);
            }
        });
    }
}
