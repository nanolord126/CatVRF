<?php

declare(strict_types=1);

namespace App\Domains\WeddingPlanning\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

/**
 * WeddingBooking Model
 */
final class WeddingBooking extends Model
{
    protected $table = 'wedding_bookings';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'event_id',
        'bookable_type',
        'bookable_id',
        'amount',
        'prepayment_amount',
        'status',
        'booked_at',
        'idempotency_key',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'amount' => 'integer',
        'prepayment_amount' => 'integer',
        'booked_at' => 'datetime',
        'tags' => 'json',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant_id', function (Builder $builder) {
            if (function_exists('tenant') && is_object(tenant()) && isset(tenant()->id)) {
                $builder->where('wedding_bookings.tenant_id', tenant()->id);
            }
        });

        static::creating(function (Model $model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
            if (function_exists('tenant') && is_object(tenant()) && isset(tenant()->id)) {
                $model->tenant_id = $model->tenant_id ?? tenant()->id;
            }
        });
    }

    /**
     * Relation: Event
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(WeddingEvent::class, 'event_id');
    }

    /**
     * Morph relation for Package or Vendor
     */
    public function bookable(): MorphTo
    {
        return $this->morphTo();
    }
}
