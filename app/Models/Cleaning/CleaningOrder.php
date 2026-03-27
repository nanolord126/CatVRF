<?php

declare(strict_types=1);

namespace App\Models\Cleaning;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use App\Models\User;

/**
 * CleaningOrder.
 * Transactional entity for cleaning service bookings.
 * Follows 2026 Canonical with photos_before/after for QA.
 */
final class CleaningOrder extends Model
{
    protected $table = 'cleaning_orders';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'user_id',
        'cleaning_company_id',
        'cleaning_service_id',
        'cleaning_address_id',
        'status', // pending, confirmed, in_progress, inspected, completed, cancelled
        'scheduled_at',
        'started_at',
        'finished_at',
        'total_cents',
        'prepayment_cents',
        'payment_status', // unpaid, authorized, captured, refunded
        'photos_before',
        'photos_after',
        'client_wishes',
        'inspection_data',
        'correlation_id',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'total_cents' => 'integer',
        'prepayment_cents' => 'integer',
        'photos_before' => 'json',
        'photos_after' => 'json',
        'inspection_data' => 'json',
        'tenant_id' => 'integer',
        'user_id' => 'integer',
        'cleaning_company_id' => 'integer',
        'cleaning_service_id' => 'integer',
        'cleaning_address_id' => 'integer',
    ];

    /**
     * Boot logic for metadata and tenant isolation.
     */
    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
            $model->tenant_id = $model->tenant_id ?? (int) (tenant()->id ?? 0);
            $model->correlation_id = $model->correlation_id ?? (string) Str::uuid();
        });

        static::addGlobalScope('tenant', function ($query) {
            if (tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });
    }

    /**
     * Client owner of the order.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Company providing the cleaning.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(CleaningCompany::class, 'cleaning_company_id');
    }

    /**
     * Specific service type.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(CleaningService::class, 'cleaning_service_id');
    }

    /**
     * Locations associated with the order.
     */
    public function address(): BelongsTo
    {
        return $this->belongsTo(CleaningAddress::class, 'cleaning_address_id');
    }

    /**
     * Review associated with this order (one review per order).
     */
    public function review(): belongsTo
    {
        return $this->hasOne(CleaningReview::class);
    }

    /**
     * Safety check for photo evidence.
     */
    public function canComplete(): bool
    {
        return !empty($this->photos_after);
    }
}
