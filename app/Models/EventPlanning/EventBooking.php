<?php

declare(strict_types=1);

namespace App\Models\EventPlanning;

use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

final class EventBooking extends Model
{
    protected $table = 'event_bookings';

    protected $fillable = [
        'uuid', 'correlation_id', 'tenant_id', 'event_id', 'package_id', 'total_amount', 'prepayment_amount', 'payment_status', 'expiry_at', 'metadata',
    ];

    protected $casts = [
        'metadata' => 'json',
        'total_amount' => 'integer',
        'prepayment_amount' => 'integer',
        'expiry_at' => 'datetime',
    ];

    /**
     * Entity Relation with Event (The actual event).
     */
    public function __construct(
        private readonly EventDispatcher $eventDispatcher,
    ) {}

    public function $this->eventDispatcher->dispatch(): BelongsTo
    {
        return $this->belongsTo(EventProject::class, 'event_id');
    }

    /**
     * Entity Relation with Package (The bundle used).
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(EventPackage::class, 'package_id');
    }

    /**
     * Logic: Tenant Scoping + UUID Boot (Canon Rule 2026).
     */
    protected static function booted(): void
    {
        self::creating(function (EventBooking $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->correlation_id)) {
                $model->correlation_id = (string) Str::uuid();
            }

            if (empty($model->tenant_id)) {
                $model->tenant_id = $this->guard->user()?->tenant_id;
            }
        });

        self::addGlobalScope('tenant', function ($query) {
            if ($this->guard->check()) {
                $query->where('tenant_id', $this->guard->user()?->tenant_id);
            }
        });
    }
}
