<?php

declare(strict_types=1);

namespace App\Models\EventPlanning;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * EventBooking Model (Financial Transaction/Record).
 * Implementation: Layer 2 (Domain/Database Layer).
 */
final class EventBooking extends Model
{
    use HasFactory;

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
     * Logic: Tenant Scoping + UUID Boot (Canon Rule 2026).
     */
    protected static function booted(): void
    {
        static::creating(function (EventBooking $model) {
            $model->uuid = (string) Str::uuid();
            $model->correlation_id = (string) Str::uuid();

            if (empty($model->tenant_id)) {
                $model->tenant_id = auth()->user()?->tenant_id;
            }
        });

        static::addGlobalScope('tenant', function ($query) {
            if (auth()->check()) {
                $query->where('tenant_id', auth()->user()?->tenant_id);
            }
        });
    }

    /**
     * Entity Relation with Event (The actual event).
     */
    public function event(): BelongsTo
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
}
