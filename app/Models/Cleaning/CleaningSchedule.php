<?php

declare(strict_types=1);

namespace App\Models\Cleaning;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * CleaningSchedule.
 * Operational work schedule for cleaners and task allocation.
 * Follows 2026 Canonical with shift tracking.
 */
final class CleaningSchedule extends Model
{
    protected $table = 'cleaning_schedules';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'cleaning_company_id',
        'cleaner_id',
        'cleaning_order_id',
        'start_time',
        'end_time',
        'type', // work, break, task, unavailable
        'correlation_id',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'cleaner_id' => 'integer',
        'cleaning_company_id' => 'integer',
        'cleaning_order_id' => 'integer',
        'tenant_id' => 'integer',
    ];

    /**
     * Boot logic for metadata and tenant isolation.
     */
    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
            $model->tenant_id = $model->tenant_id ?? (int) (tenant()->id ?? 0);
        });

        static::addGlobalScope('tenant', function ($query) {
            if (tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });
    }

    /**
     * Company providing the worker.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(CleaningCompany::class, 'cleaning_company_id');
    }

    /**
     * Specific order assigned for this time slot.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(CleaningOrder::class, 'cleaning_order_id');
    }

    /**
     * Staff member / cleanerassigned for this task.
     */
    public function cleaner(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'cleaner_id');
    }

    /**
     * Collision check logic for worker.
     */
    public function overlapsWith(self $other): bool
    {
        return $this->start_time < $other->end_time && $this->end_time > $other->start_time;
    }

    /**
     * Duration for shift (task or work).
     */
    public function durationMinutes(): int
    {
        return (int) $this->start_time->diffInMinutes($this->end_time);
    }
}
