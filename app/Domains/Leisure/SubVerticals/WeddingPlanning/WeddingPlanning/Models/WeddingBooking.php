<?php

declare(strict_types=1);

namespace App\Domains\Leisure\SubVerticals\WeddingPlanning\Models;

use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

final class WeddingBooking extends Model
{
    use TenantScoped;

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

    /**
     * Relation: Event
     */
    public function __construct(
        private readonly EventDispatcher $eventDispatcher,
    ) {}

    public function $this->eventDispatcher->dispatch(): BelongsTo
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

    protected static function booted(): void
    {
        self::addGlobalScope('tenant_id', function (Builder $builder) {
            if (function_exists('tenant') && is_object(tenant()) && isset(tenant()->id)) {
                $builder->where('wedding_bookings.tenant_id', tenant()->id);
            }
        });

        self::creating(function (Model $model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
            if (function_exists('tenant') && is_object(tenant()) && isset(tenant()->id)) {
                $model->tenant_id = $model->tenant_id ?? tenant()->id;
            }
        });
    }
}
